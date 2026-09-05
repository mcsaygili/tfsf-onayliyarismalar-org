<?php

namespace App\Http\Controllers\Institution;

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
        return view('institution.profile.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:institution'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        app(ChangeAccountPassword::class)->change($request, 'institution', $validated);

        return redirect()->route('institution.password.edit')->with('status', __('institution.password.updated'));
    }
}
