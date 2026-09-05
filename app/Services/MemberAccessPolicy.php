<?php

namespace App\Services;

use App\Models\User;

class MemberAccessPolicy
{
    public function denialReason(User $user): ?string
    {
        if ($user->status !== 1) {
            return __('auth.account_inactive');
        }

        return $user->activeRestrictions()->where('type', 'account')->exists()
            ? __('auth.account_restricted')
            : null;
    }
}
