<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChangeAccountPassword
{
    public function change(Request $request, string $guardName, array $validated): void
    {
        $guard = Auth::guard($guardName);
        $account = $guard->user();
        $current = $account->getConnection()->transaction(function () use ($account, $validated) {
            $current = $account->newQuery()->whereKey($account->getKey())->lockForUpdate()->firstOrFail();
            if (! Hash::check($validated['current_password'], $current->getAuthPassword())) {
                throw ValidationException::withMessages(['current_password' => __('auth.password')])->errorBag('updatePassword');
            }
            abort_if(app(PanelAccountAccess::class)->denialReason($current) !== null, 403);
            $current->forceFill(['password' => Hash::make($validated['password']), 'remember_token' => Str::random(60)])->save();

            return $current;
        }, 3);
        $guard->setUser($current);
        $request->session()->regenerate();
        app(PanelSession::class)->stamp($request->session(), $current, $guardName);
    }
}
