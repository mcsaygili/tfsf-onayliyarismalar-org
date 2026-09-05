<?php

namespace App\Services;

use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Model;

class VerifyAccountEmail
{
    public function verify(Model $account, string $emailHash): void
    {
        $connection = $account->getConnection();
        $connection->transaction(function () use ($account, $emailHash, $connection): void {
            $current = $account->newQuery()->whereKey($account->getKey())->lockForUpdate()->firstOrFail();
            // Recheck under lock: the email may have changed since HTTP authorization.
            abort_unless(hash_equals(sha1($current->getEmailForVerification()), $emailHash), 403);
            if (! $current->hasVerifiedEmail() && $current->markEmailAsVerified()) {
                $connection->afterCommit(fn () => event(new Verified($current)));
            }
        }, 3);
    }
}
