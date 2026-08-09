<?php

namespace App\Http\Controllers\Eys\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eys\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('eys.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('eys.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('eys')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('eys.login');
    }
}
