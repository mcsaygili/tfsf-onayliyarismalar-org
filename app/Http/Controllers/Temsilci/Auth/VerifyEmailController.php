<?php

namespace App\Http\Controllers\Temsilci\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user('temsilci');

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('temsilci.dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('temsilci.dashboard', absolute: false).'?verified=1');
    }
}
