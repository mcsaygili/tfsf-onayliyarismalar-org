<?php

namespace App\Auth;

use App\Services\AccountSecurityContext;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class BoundAccountProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $account = parent::retrieveByCredentials($credentials);
        if ($account && ! app(AccountSecurityContext::class)->matches($account, $account->remember_context)) {
            // A successful fresh login with Remember Me must issue a new token.
            // Do not save here: credential lookup alone does not authorize a write.
            $account->setRememberToken(null);
        }

        return $account;
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $account = parent::retrieveByToken($identifier, $token);

        return $account && app(AccountSecurityContext::class)->matches($account, $account->remember_context) ? $account : null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token)
    {
        $user->remember_context = app(AccountSecurityContext::class)->fingerprint($user);
        parent::updateRememberToken($user, $token);
    }
}
