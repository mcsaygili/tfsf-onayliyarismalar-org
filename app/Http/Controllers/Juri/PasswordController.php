<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use App\Services\ChangeAccountPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:juri'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        app(ChangeAccountPassword::class)->change($request, 'juri', $validated);

        return redirect()->route('juri.password.edit')->with('status', __('juri.password.updated'));
    }
}
