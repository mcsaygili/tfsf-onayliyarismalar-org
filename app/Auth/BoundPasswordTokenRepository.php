<?php

namespace App\Auth;

use App\Services\AccountSecurityContext;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Carbon;

class BoundPasswordTokenRepository extends DatabaseTokenRepository
{
    public function create(CanResetPassword $user)
    {
        return $this->connection->transaction(function () use ($user) {
            $current = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if ($current->getEmailForPasswordReset() !== $user->getEmailForPasswordReset()) {
                throw new \LogicException('Password reset identity changed.');
            }
            $token = parent::create($current);
            $this->getTable()->where('email', $current->getEmailForPasswordReset())
                ->update(['user_id' => $current->getKey(), 'security_context' => app(AccountSecurityContext::class)->fingerprint($current)]);

            return $token;
        }, 3);
    }

    public function exists(CanResetPassword $user, #[\SensitiveParameter] $token)
    {
        $record = $this->getTable()->where('email', $user->getEmailForPasswordReset())->lockForUpdate()->first();

        return $record && $record->user_id === $user->getKey()
            && app(AccountSecurityContext::class)->matches($user, $record->security_context)
            && $record->created_at !== null
            && ! $this->tokenExpired($record->created_at)
            && $this->hasher->check($token, $record->token);
    }

    public function recentlyCreatedToken(CanResetPassword $user)
    {
        $record = $this->getTable()->where('email', $user->getEmailForPasswordReset())->lockForUpdate()->first();

        return $record && app(AccountSecurityContext::class)->matches($user, $record->security_context) && $record->created_at !== null && $this->tokenRecentlyCreated($record->created_at);
    }

    protected function tokenExpired($createdAt)
    {
        return Carbon::parse($createdAt)->addSeconds($this->expires)->lte(now());
    }
}
