<?php

namespace App\Http\Controllers\Eys;

use App\Enums\Module;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

/**
 * EYS yönetici paneli — Rol (Role) yönetimi.
 *
 * Roller modül (Spatie "team") bazlıdır: her rol bir modüle (team_id) aittir
 * ve yalnızca o modülün izinlerini taşıyabilir. Aynı rol adı farklı
 * modüllerde birbirinden bağımsız olarak bulunabilir.
 */
class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Role::query()->with('permissions');

        $fModule = $request->query('module');
        if ($fModule !== null && $fModule !== '') {
            $query->where('team_id', $this->moduleValue($fModule));
        }

        $fSearch = trim((string) $request->query('q', ''));
        if ($fSearch !== '') {
            $query->where(function ($q) use ($fSearch) {
                $q->where('name', 'like', "%{$fSearch}%")
                    ->orWhere('label', 'like', "%{$fSearch}%");
            });
        }

        $roles = $query->orderBy('team_id')->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $userCounts = DB::table('model_has_roles')
            ->select('role_id', DB::raw('count(*) as c'))
            ->groupBy('role_id')
            ->pluck('c', 'role_id');

        return view('eys.roles.index', [
            'roles' => $roles,
            'userCounts' => $userCounts,
            'moduleLabels' => $this->moduleLabels(),
            'moduleByValue' => $this->moduleByValue(),
            'filterModule' => $fModule,
            'filterSearch' => $fSearch,
        ]);
    }

    public function create(): View
    {
        return view('eys.roles.create', [
            'moduleLabels' => $this->moduleLabels(),
            'permissionsByModule' => $this->permissionsByModule(),
            'selectedPermissions' => [],
            'selectedModule' => old('module', Module::Eys->name),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $module = constant(Module::class.'::'.$data['module']);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($module->value);

        $role = Role::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'guard_name' => 'eys',
            'team_id' => $module->value,
        ]);

        $role->syncPermissions($this->validPermissions($data['permissions'] ?? [], $module));
        $registrar->forgetCachedPermissions();

        return redirect()->route('eys.roles.index')->with('status', __('eys.role.created'));
    }

    public function edit(Role $role): View
    {
        $module = Module::from((int) $role->team_id);

        return view('eys.roles.edit', [
            'role' => $role,
            'module' => $module,
            'moduleLabels' => $this->moduleLabels(),
            'permissionsByModule' => $this->permissionsByModule(),
            'selectedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $module = Module::from((int) $role->team_id);
        $data = $this->validateData($request, $role, $module);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($module->value);

        $role->update([
            'name' => $data['name'],
            'label' => $data['label'],
        ]);

        $role->syncPermissions($this->validPermissions($data['permissions'] ?? [], $module));
        $registrar->forgetCachedPermissions();

        return redirect()->route('eys.roles.index')->with('status', __('eys.role.updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $inUse = DB::table('model_has_roles')->where('role_id', $role->getKey())->exists();
        if ($inUse) {
            return redirect()->route('eys.roles.index')->with('status', __('eys.role.delete_in_use'));
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('eys.roles.index')->with('status', __('eys.role.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?Role $role = null, ?Module $module = null): array
    {
        // Modül başına benzersiz rol adı (team_id, name, guard_name).
        $uniqueName = function (string $attribute, mixed $value, \Closure $fail) use ($request, $role, $module) {
            $teamId = $module?->value ?? $this->moduleValue((string) $request->input('module', ''));
            if ($teamId === null) {
                return;
            }

            $exists = Role::where('name', $value)
                ->where('team_id', $teamId)
                ->when($role, fn ($q) => $q->where('id', '!=', $role->getKey()))
                ->exists();

            if ($exists) {
                $fail(__('validation.unique', ['attribute' => __('eys.role.name')]));
            }
        };

        $rules = [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_\-\.]+$/', $uniqueName],
            'label' => ['required', 'string', 'max:128'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];

        if ($role === null) {
            $modules = array_map(fn (Module $m) => $m->name, Module::cases());
            $rules['module'] = ['required', 'string', 'in:'.implode(',', $modules)];
        }

        return $request->validate($rules);
    }

    /**
     * Yalnızca seçilen modüle ait gerçek izinleri döndür (yetki sızıntısını engelle).
     *
     * @param  array<int, string>  $names
     * @return Collection<int, Permission>
     */
    private function validPermissions(array $names, Module $module)
    {
        if (empty($names)) {
            return collect();
        }

        return Permission::where('module', $module->name)
            ->whereIn('name', $names)
            ->get();
    }

    /** @return array<string, Collection> Module name => grup bazlı izinler. */
    private function permissionsByModule(): array
    {
        $byModule = [];
        $all = Permission::orderBy('group')->orderBy('name')->get()->groupBy('module');

        foreach (Module::cases() as $module) {
            $perms = $all->get($module->name, collect());
            $byModule[$module->name] = $perms->groupBy(fn (Permission $p) => $p->group ?: '—');
        }

        return $byModule;
    }

    /** @return array<string, string> */
    private function moduleLabels(): array
    {
        $labels = [];
        foreach (Module::cases() as $module) {
            $labels[$module->name] = $module->label();
        }

        return $labels;
    }

    /** @return array<int, string> team_id => görünen etiket. */
    private function moduleByValue(): array
    {
        $map = [];
        foreach (Module::cases() as $module) {
            $map[$module->value] = $module->label();
        }

        return $map;
    }

    private function moduleValue(string $name): ?int
    {
        foreach (Module::cases() as $module) {
            if ($module->name === $name) {
                return $module->value;
            }
        }

        return null;
    }
}
