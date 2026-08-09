<?php

namespace App\Http\Controllers\Eys;

use App\Enums\Module;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

/**
 * EYS yönetici paneli — İzin (Permission) yönetimi.
 *
 * İzinler global satırlardır (Spatie teams modunda team_id taşımaz). Her
 * izin bir modüle ve modül içindeki bir bileşene (group) aittir. Ad deseni:
 * {modul_key}.{bilesen}.{aksiyon} (ör: eys.users.view).
 */
class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Permission::query();

        $fModule = $request->query('module');
        if ($fModule !== null && $fModule !== '') {
            $query->where('module', $fModule);
        }

        $fSearch = trim((string) $request->query('q', ''));
        if ($fSearch !== '') {
            $query->where(function ($q) use ($fSearch) {
                $q->where('name', 'like', "%{$fSearch}%")
                    ->orWhere('label', 'like', "%{$fSearch}%")
                    ->orWhere('group', 'like', "%{$fSearch}%");
            });
        }

        $permissions = $query->orderBy('module')->orderBy('group')->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('eys.permissions.index', [
            'permissions' => $permissions,
            'moduleLabels' => $this->moduleLabels(),
            'filterModule' => $fModule,
            'filterSearch' => $fSearch,
        ]);
    }

    public function create(): View
    {
        return view('eys.permissions.create', [
            'moduleLabels' => $this->moduleLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Permission::create([
            'name' => $data['name'],
            'guard_name' => 'eys',
            'module' => $data['module'],
            'group' => $data['group'] ?: null,
            'label' => $data['label'] ?: null,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('eys.permissions.index')->with('status', __('eys.permission.created'));
    }

    public function edit(Permission $permission): View
    {
        return view('eys.permissions.edit', [
            'moduleLabels' => $this->moduleLabels(),
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $this->validateData($request, $permission);

        $permission->update([
            'name' => $data['name'],
            'module' => $data['module'],
            'group' => $data['group'] ?: null,
            'label' => $data['label'] ?: null,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('eys.permissions.index')->with('status', __('eys.permission.updated'));
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->roles()->exists()) {
            return redirect()->route('eys.permissions.index')->with('status', __('eys.permission.delete_in_use'));
        }

        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('eys.permissions.index')->with('status', __('eys.permission.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request, ?Permission $permission = null): array
    {
        $modules = array_map(fn (Module $m) => $m->name, Module::cases());

        $nameRule = ['required', 'string', 'max:128', 'regex:/^[a-z0-9_\-\.]+$/'];
        $nameRule[] = $permission
            ? "unique:permissions,name,{$permission->getKey()},id"
            : 'unique:permissions,name';

        return $request->validate([
            'name' => $nameRule,
            'module' => ['required', 'string', 'in:'.implode(',', $modules)],
            'group' => ['nullable', 'string', 'max:128'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** @return array<string, string> Module case name => görünen etiket. */
    private function moduleLabels(): array
    {
        $labels = [];
        foreach (Module::cases() as $module) {
            $labels[$module->name] = $module->label();
        }

        return $labels;
    }
}
