<?php

namespace App\Support\FileManager;

use App\Enums\Module;
use App\Models\EysUser;
use Illuminate\Support\Facades\DB;

/**
 * FileManager context (kapsam) erişim kontrolü.
 *
 * Kurallar:
 *  - EYS yöneticisi (eys.file_manager.manage izni, Eys team'inde) → TÜM context'ler.
 *  - Modül context'i (kurum/temsilci/juri/uye) → kullanıcının o modülün
 *    team'inde herhangi bir rolü varsa (yani o modülün içeriğini yönetebiliyorsa).
 *  - 'common' (paylaşımlı) → herhangi bir modülde rolü olan her kullanıcı.
 *  - 'all' (tüm disk) → yalnız EYS yöneticisi.
 */
class ContextAccess
{
    public function canAccess(EysUser $user, string $context): bool
    {
        $contexts = config('filemanager.contexts', []);
        if (! isset($contexts[$context])) {
            return false;
        }

        $eysAdmin = $this->teamHasPermission($user, Module::Eys->value, 'eys.file_manager.manage');

        if ($context === 'all') {
            return $eysAdmin;
        }
        if ($eysAdmin) {
            return true;
        }

        $moduleName = $contexts[$context]['module'] ?? null;

        if ($moduleName === null) {
            // 'common' → en az bir modülde rolü olan herkes.
            return $this->hasAnyRole($user);
        }

        $module = constant(Module::class.'::'.$moduleName);

        return $this->hasAnyRoleInTeam($user, $module->value);
    }

    /** @return array<int, array{key: string, label: string}> Kullanıcının erişebildiği context'ler. */
    public function allowedContexts(EysUser $user): array
    {
        $out = [];
        foreach (config('filemanager.contexts', []) as $key => $cfg) {
            if ($this->canAccess($user, $key)) {
                $out[] = ['key' => $key, 'label' => $cfg['label']];
            }
        }

        return $out;
    }

    private function teamHasPermission(EysUser $user, int $teamId, string $permission): bool
    {
        return DB::table('model_has_roles as mhr')
            ->join('role_has_permissions as rhp', 'rhp.role_id', '=', 'mhr.role_id')
            ->join('permissions as p', 'p.id', '=', 'rhp.permission_id')
            ->where('mhr.model_id', $user->getKey())
            ->where('mhr.model_type', $user->getMorphClass())
            ->where('mhr.team_id', $teamId)
            ->where('p.name', $permission)
            ->exists();
    }

    private function hasAnyRoleInTeam(EysUser $user, int $teamId): bool
    {
        return DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->where('model_type', $user->getMorphClass())
            ->where('team_id', $teamId)
            ->exists();
    }

    private function hasAnyRole(EysUser $user): bool
    {
        return DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->where('model_type', $user->getMorphClass())
            ->exists();
    }
}
