<?php

namespace App\Http\Controllers\Temsilci\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user('temsilci')->hasVerifiedEmail()
                    ? redirect()->intended(route('temsilci.dashboard', absolute: false))
                    : view('temsilci.auth.verify-email');
    }
}
