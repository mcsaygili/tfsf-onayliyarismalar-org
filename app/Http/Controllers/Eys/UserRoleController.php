<?php

namespace App\Http\Controllers\Eys;

use App\Enums\Module;
use App\Http\Controllers\Controller;
use App\Models\EysUser;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

/**
 * EYS yönetici paneli — kullanıcının MODÜL BAZLI rol yönetimi.
 *
 * Roller global değil, her modül bir Spatie "team"dir. Aynı EYS kullanıcısı
 * farklı modüllerde farklı (ve birden fazla) role sahip olabilir. Bu
 * controller bir kullanıcının her modüldeki rollerini tek ekranda yönetir.
 */
class UserRoleController extends Controller
{
    public function edit(EysUser $user): View
    {
        $registrar = app(PermissionRegistrar::class);
        $teamKey = $registrar->teamsKey;

        // Kullanıcının her modüldeki mevcut rolleri: [module_value => [role_name, ...]]
        $rows = DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', $user->getMorphClass())
            ->where('mhr.model_id', $user->id)
            ->get(['r.name', "mhr.$teamKey as team_id"]);

        $current = [];
        foreach ($rows as $row) {
            $current[$row->team_id][] = $row->name;
        }

        // Modül başına atanabilir roller (Rol yönetiminde tanımlananlar dahil).
        $rolesByModule = [];
        foreach (Module::cases() as $module) {
            $rolesByModule[$module->value] = Role::where('team_id', $module->value)
                ->orderBy('name')
                ->get(['id', 'name', 'label']);
        }

        return view('eys.users.roles', [
            'user' => $user,
            'modules' => Module::cases(),
            'rolesByModule' => $rolesByModule,
            'current' => $current,
        ]);
    }

    public function update(Request $request, EysUser $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['array'],
            'roles.*.*' => ['string'],
        ]);

        $registrar = app(PermissionRegistrar::class);
        $submitted = $validated['roles'] ?? [];

        foreach (Module::cases() as $module) {
            $registrar->setPermissionsTeamId($module->value);
            $user->unsetRelation('roles');

            $requested = $submitted[$module->value] ?? [];

            // Yalnızca bu modülde gerçekten var olan rolleri uygula.
            $validRoles = Role::where('team_id', $module->value)
                ->whereIn('name', is_array($requested) ? $requested : [])
                ->pluck('name')
                ->all();

            $user->syncRoles($validRoles);
        }

        $registrar->forgetCachedPermissions();

        return redirect()->route('eys.users.index')->with('status', __('eys.user.roles_updated', [
            'name' => trim($user->first_name.' '.$user->last_name) ?: $user->email,
        ]));
    }
}
