<?php

namespace App\Http\Controllers\Juri\Auth;

use App\Http\Controllers\Controller;
use App\Services\VerifyAccountEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        app(VerifyAccountEmail::class)->verify($request->user('juri'), (string) $request->route('hash'));

        return redirect()->intended(route('juri.dashboard', absolute: false).'?verified=1');
    }
}
