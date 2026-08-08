<?php

namespace App\Http\Controllers\Temsilci\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Temsilci\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('temsilci.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('temsilci.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('temsilci')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('temsilci.login');
    }
}
