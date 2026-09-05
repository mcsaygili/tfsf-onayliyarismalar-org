<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\JurySessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JurySessionController extends Controller
{
    public function declaration(Request $request, Competition $competition, JurySessionService $sessions): RedirectResponse
    {
        $sessions->declare($competition, $request->user('juri'), $request->all());

        return back()->with('status', __('jury_session.declared'));
    }
}
