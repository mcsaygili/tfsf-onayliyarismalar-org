<?php

namespace App\Policies;

use App\Enums\Module;
use App\Models\EysUser;
use Spatie\Permission\PermissionRegistrar;

class EysUserPolicy
{
    public function viewAny(EysUser $actor): bool
    {
        return $this->permitted($actor, 'view');
    }

    public function create(EysUser $actor): bool
    {
        return $this->permitted($actor, 'create');
    }

    public function update(EysUser $actor, EysUser $target): bool
    {
        return ($actor->status && $actor->is($target)) || $this->permitted($actor, 'edit');
    }

    public function manageIdentity(EysUser $actor): bool
    {
        return $this->permitted($actor, 'manage');
    }

    private function permitted(EysUser $actor, string $action): bool
    {
        return $actor->status && app(PermissionRegistrar::class)->getPermissionsTeamId() === Module::Eys->value
            && ($actor->can('eys.users.'.$action) || $actor->can('eys.users.manage'));
    }
}
