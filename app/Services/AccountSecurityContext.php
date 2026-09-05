<?php

namespace App\Services;

use App\Models\InstitutionStaff;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class AccountSecurityContext
{
    public function fingerprint(Authenticatable $account): string
    {
        $parts = ['account-security-v1', $account::class, $account->getAuthIdentifier(), $account->security_stamp,
            $account->getAuthPassword(), $account->email, $account->phone_number ?? $account->phone, $account->status];
        $locking = $account->getConnection()->transactionLevel() > 0;
        if ($account instanceof InstitutionStaff) {
            $parts[] = $account->account_kind;
            $query = $account->institution();
            if ($locking) {
                $query->lockForUpdate();
            }
            $institution = $query->first();
            $parts[] = [$account->institution_id, $institution?->security_stamp, $institution?->status];
        }
        if ($account instanceof User) {
            // Keep started restrictions in the fingerprint after natural expiry.
            // An idle device must not regain its old session after a scheduled ban.
            $query = $account->restrictions()->where('type', 'account')->where('starts_at', '<=', now())
                ->where(fn ($q) => $q->whereNull('lifted_at')->orWhereColumn('lifted_at', '>', 'starts_at'))->orderBy('id');
            if ($locking) {
                $query->lockForUpdate();
            }
            $parts[] = $query->pluck('id')->all();
        }

        return hash_hmac('sha256', json_encode($parts, JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function matches(Authenticatable $account, ?string $fingerprint): bool
    {
        return is_string($fingerprint) && $fingerprint !== '' && hash_equals($this->fingerprint($account), $fingerprint);
    }
}
