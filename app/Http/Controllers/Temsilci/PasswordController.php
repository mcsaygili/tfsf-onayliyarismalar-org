<?php

namespace App\Http\Controllers\Temsilci;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('temsilci.profile.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $temsilci = Auth::guard('temsilci')->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:temsilci'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $temsilci->update(['password' => $validated['password']]);

        return redirect()->route('temsilci.password.edit')->with('status', __('temsilci.password.updated'));
    }
}
