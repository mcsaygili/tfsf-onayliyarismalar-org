<?php

namespace Database\Seeders;

use App\Enums\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie teams modunda bileşen-bazlı izin + modül-bazlı rol üretimi:
 *  - permissions: global satırlar (team_id YOK), config/permissions.php'den üretilir.
 *    Ad deseni: {modul_key}.{bilesen}.{aksiyon} (ör: eys.users.view)
 *  - roles: her modül (team) için ayrı rol seti (team_id VAR)
 *  - model_has_roles: atamalar modül bazında (team_id VAR)
 *
 * guard_name her yerde 'eys' — sistemde rol/izin alan tek model App\Models\EysUser
 * (guard: eys), bkz. config/permission.php ve App\Models\EysUser'daki HasRoles notu.
 */
class ModuleRoleSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $registry = config('permissions.modules', []);
        $actionLabels = config('permissions.action_labels', []);
        $defaultRoles = config('permissions.default_roles', []);

        // 1. Global izinleri config kayıt defterinden üret.
        foreach (Module::cases() as $module) {
            $components = $registry[$module->name]['components'] ?? [];

            foreach ($components as $componentKey => $component) {
                $groupLabel = $component['label'] ?? $componentKey;

                foreach ($component['actions'] ?? [] as $action) {
                    Permission::updateOrCreate(
                        [
                            'name' => "{$module->key()}.{$componentKey}.{$action}",
                            'guard_name' => 'eys',
                        ],
                        [
                            'module' => $module->name,
                            'group' => $groupLabel,
                            'label' => $groupLabel.' · '.($actionLabels[$action] ?? $action),
                        ]
                    );
                }
            }
        }

        // 2. Her modül için varsayılan rolleri oluştur ve izinleri ata (team context).
        foreach (Module::cases() as $module) {
            $registrar->setPermissionsTeamId($module->value);

            $modulePermissions = Permission::where('module', $module->name)->get();

            foreach ($defaultRoles as $roleName => $roleDef) {
                $label = $roleDef['label'] ?? $roleName;

                $role = Role::firstOrCreate(
                    [
                        'name' => $roleName,
                        'guard_name' => 'eys',
                        'team_id' => $module->value,
                    ],
                    ['label' => $label]
                );

                if ($role->label !== $label) {
                    $role->update(['label' => $label]);
                }

                $allowed = $roleDef['actions']; // null => tüm aksiyonlar
                $perms = $modulePermissions->filter(function (Permission $permission) use ($allowed) {
                    if ($allowed === null) {
                        return true;
                    }

                    return in_array(Str::afterLast($permission->name, '.'), $allowed, true);
                });

                $role->syncPermissions($perms);
            }
        }

        $registrar->forgetCachedPermissions();
    }
}
