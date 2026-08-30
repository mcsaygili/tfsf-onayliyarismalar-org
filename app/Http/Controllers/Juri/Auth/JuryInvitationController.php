<?php

namespace App\Http\Controllers\Juri\Auth;

use App\Http\Controllers\Controller;
use App\Services\JuryInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class JuryInvitationController extends Controller
{
    public function create(string $token, JuryInvitationService $service): View
    {
        $invitation = $service->findPendingByToken($token);
        app()->setLocale($invitation->locale);
        $service->markOpened($invitation);
        $existingJuror = $service->existingJurorFor($invitation);

        return view('juri.auth.invitation', compact('invitation', 'token', 'existingJuror'));
    }

    public function store(Request $request, string $token, JuryInvitationService $service): RedirectResponse
    {
        $invitation = $service->findPendingByToken($token);
        app()->setLocale($invitation->locale);

        $existingJuror = $service->existingJurorFor($invitation);
        $validated = $existingJuror ? [] : $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        [$juror, $created] = $service->accept($invitation, $validated);

        if (! $created) {
            return redirect()->route('juri.login')->with('status', __('juri.invitation.account_linked'));
        }

        Auth::guard('juri')->login($juror);
        $request->session()->regenerate();

        return redirect()->route('juri.dashboard')->with('status', __('juri.invitation.accepted'));
    }

    public function decline(string $token, JuryInvitationService $service): RedirectResponse
    {
        $invitation = $service->findPendingByToken($token);
        app()->setLocale($invitation->locale);
        $service->decline($invitation);

        return redirect()->route('juri.login')->with('status', __('juri.invitation.declined'));
    }
}
