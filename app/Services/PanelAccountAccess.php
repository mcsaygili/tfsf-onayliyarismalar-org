<?php

namespace App\Services;

use App\Models\InstitutionStaff;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class PanelAccountAccess
{
    public function denialReason(Authenticatable $account): ?string
    {
        if ($account instanceof User) {
            return app(MemberAccessPolicy::class)->denialReason($account);
        }
        if (! $account->status) {
            return __('auth.account_inactive');
        }
        if ($account instanceof InstitutionStaff && ! $account->institution()->where('status', true)->exists()) {
            return __('auth.institution_inactive');
        }

        return null;
    }
}
