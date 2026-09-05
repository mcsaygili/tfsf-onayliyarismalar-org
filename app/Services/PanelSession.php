<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;

class PanelSession
{
    public function key(string $guard): string
    {
        return 'auth_session_signature.'.$guard;
    }

    public function signature(Authenticatable $account, string $guard): string
    {
        return hash_hmac('sha256', $guard."\0".$account->getAuthIdentifier()."\0".app(AccountSecurityContext::class)->fingerprint($account), config('app.key'));
    }

    public function stamp(Session $session, Authenticatable $account, string $guard): void
    {
        $session->put($this->key($guard), $this->signature($account, $guard));
    }

    public function matches(Session $session, Authenticatable $account, string $guard): bool
    {
        $stored = $session->get($this->key($guard));

        return is_string($stored) && hash_equals($this->signature($account, $guard), $stored);
    }
}
