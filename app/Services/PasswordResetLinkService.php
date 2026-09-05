<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetLinkService
{
    public function send(Request $request, string $broker): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'string', 'email', 'max:254']]);
        $key = 'password-reset-link:'.$broker.':'.hash('sha256', mb_strtolower($validated['email']));
        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 600);
            try {
                Password::broker($broker)->sendResetLink($validated);
            } catch (\Throwable $exception) {
                Log::warning('Password reset link delivery failed.', ['broker' => $broker, 'exception_type' => $exception::class]);
            }
        }

        return back()->with('status', __('passwords.requested'));
    }
}
