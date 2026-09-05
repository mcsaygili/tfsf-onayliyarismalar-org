<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\JurySessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JurySessionController extends Controller
{
    public function update(Request $request, Competition $competition, JurySessionService $sessions): RedirectResponse
    {
        $sessions->update($competition, $request->user('eys'), $request->all());

        return back()->with('status', __('jury_session.saved'));
    }
}
