<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use App\Http\Requests\Uye\ProfileUpdateRequest;
use App\Models\EducationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('uye.profile.edit', [
            'user' => $request->user(),
            'educationLevels' => EducationLevel::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function passwordEdit(): View
    {
        return view('uye.profile.password');
    }

    public function accountEdit(): View
    {
        return view('uye.profile.account');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::guard('web')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login');
    }
}
