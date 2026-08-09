<?php

namespace App\Http\Controllers\Juri;

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
        return view('juri.profile.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $juri = Auth::guard('juri')->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:juri'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $juri->update(['password' => $validated['password']]);

        return redirect()->route('juri.password.edit')->with('status', __('juri.password.updated'));
    }
}
