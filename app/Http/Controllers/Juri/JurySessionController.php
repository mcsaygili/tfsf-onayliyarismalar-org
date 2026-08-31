<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionJurySessionAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JurySessionController extends Controller
{
    public function declaration(Request $request, Competition $competition): RedirectResponse
    {
        $validated = $request->validate([
            'conflict_declared' => ['required', 'boolean'],
            'conflict_note' => ['nullable', 'required_if:conflict_declared,1', 'string', 'min:10', 'max:2000'],
        ]);
        $attendance = CompetitionJurySessionAttendance::query()
            ->where('juror_id', Auth::guard('juri')->id())
            ->whereHas('session.round', fn ($query) => $query->where('competition_id', $competition->id))
            ->firstOrFail();
        $attendance->update($validated + ['declared_at' => now()]);

        return back()->with('status', 'Çıkar çatışması beyanınız kaydedildi.');
    }
}
