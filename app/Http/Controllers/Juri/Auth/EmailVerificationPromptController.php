<?php

namespace App\Http\Controllers\Juri\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user('juri')->hasVerifiedEmail()
                    ? redirect()->intended(route('juri.dashboard', absolute: false))
                    : view('juri.auth.verify-email');
    }
}
