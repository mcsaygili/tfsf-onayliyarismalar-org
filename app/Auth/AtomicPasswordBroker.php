<?php

namespace App\Auth;

use Closure;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Passwords\PasswordBroker;

class AtomicPasswordBroker extends PasswordBroker
{
    public function reset(#[\SensitiveParameter] array $credentials, Closure $callback)
    {
        $this->timebox->dontReturnEarly();

        return $this->timebox->call(function () use ($credentials, $callback) {
            return $this->tokens->getConnection()->transaction(function () use ($credentials, $callback) {
                $user = $this->getUser($credentials);
                if (! $user) {
                    return static::INVALID_TOKEN;
                }
                // Creation, reset and SMS reset acquire the account lock first.
                $current = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->first();
                if (! $current || mb_strtolower($current->getEmailForPasswordReset()) !== mb_strtolower($credentials['email'])
                    || ! $this->tokens->exists($current, $credentials['token'])) {
                    return static::INVALID_TOKEN;
                }
                $callback($current, $credentials['password']);
                $this->tokens->delete($current);

                return static::PASSWORD_RESET;
            }, 3);
        }, $this->timeboxDuration);
    }

    public function sendResetLink(#[\SensitiveParameter] array $credentials, ?Closure $callback = null)
    {
        $this->timebox->dontReturnEarly();

        return $this->timebox->call(function () use ($credentials, $callback) {
            $challenge = $this->tokens->getConnection()->transaction(function () use ($credentials) {
                $user = $this->getUser($credentials);
                if (! $user) {
                    return static::INVALID_USER;
                }
                $user = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                if (mb_strtolower($user->getEmailForPasswordReset()) !== mb_strtolower($credentials['email'])) {
                    return static::INVALID_USER;
                }
                if ($this->tokens->recentlyCreatedToken($user)) {
                    return static::RESET_THROTTLED;
                }

                return [$user, $this->tokens->create($user)];
            }, 3);
            if (is_string($challenge)) {
                return $challenge;
            }
            [$user, $token] = $challenge;
            // External delivery must not hold a database transaction open.
            if ($callback) {
                return $callback($user, $token) ?? static::RESET_LINK_SENT;
            }
            $user->sendPasswordResetNotification($token);
            $this->events?->dispatch(new PasswordResetLinkSent($user));

            return static::RESET_LINK_SENT;
        }, $this->timeboxDuration);
    }
}
