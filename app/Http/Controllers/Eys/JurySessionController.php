<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\CompetitionAuditService;
use App\Services\JurySessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JurySessionController extends Controller
{
    public function update(Request $request, Competition $competition, JurySessionService $sessions, CompetitionAuditService $audit): RedirectResponse
    {
        $round = $competition->evaluationRounds()->where('is_final', true)->firstOrFail();
        $session = $sessions->ensureForRound($round);
        $validated = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'quorum' => ['required', 'integer', 'min:1', 'max:30'],
            'minutes' => ['nullable', 'string', 'max:10000'],
            'attendances' => ['nullable', 'array'],
            'attendances.*' => ['required', Rule::in(['invited', 'present', 'absent'])],
            'action' => ['required', Rule::in(['save', 'open', 'close'])],
        ]);

        $session->update(collect($validated)->only(['scheduled_at', 'location', 'quorum', 'minutes'])->all());
        foreach ($validated['attendances'] ?? [] as $attendanceId => $status) {
            $session->attendances()->whereKey($attendanceId)->update(['attendance_status' => $status]);
        }
        if ($validated['action'] === 'open') {
            $sessions->open($session->fresh(), Auth::guard('eys')->user());
        } elseif ($validated['action'] === 'close') {
            $sessions->close($session->fresh(), Auth::guard('eys')->user());
        }
        $audit->record($competition, 'jury_session_'.$validated['action'], Auth::guard('eys')->user(), changes: [
            'session_id' => $session->id,
            'status' => $session->fresh()->status,
            'quorum' => $session->quorum,
        ]);

        return back()->with('status', 'Final kurul oturumu güncellendi.');
    }
}
