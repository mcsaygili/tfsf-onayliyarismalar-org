<?php

namespace App\Http\Controllers\Institution;

use App\Enums\SubmissionApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\CompetitionSubmissionApproval;
use App\Services\SubmissionApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipantSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $approvals = CompetitionSubmissionApproval::query()
            ->where('approval_type', 'institution')
            ->whereHas('submission.entry.competition', fn ($query) => $query->where('institution_id', $request->user('institution')->institution_id))
            ->with(['submission.entry.user', 'submission.entry.competition.translations', 'submission.category.translations'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 ELSE 2 END")
            ->latest()->paginate(20);

        return view('institution.participant-submissions.index', compact('approvals'));
    }

    public function show(Request $request, CompetitionSubmissionApproval $approval): View
    {
        $this->authorizeInstitution($request, $approval);
        $approval->load(['submission.entry.user', 'submission.entry.competition.translations', 'submission.category.translations', 'submission.photos.captureDevice.translations']);

        return view('institution.participant-submissions.show', compact('approval'));
    }

    public function decide(Request $request, CompetitionSubmissionApproval $approval, SubmissionApprovalService $service): RedirectResponse
    {
        $this->authorizeInstitution($request, $approval);
        abort_unless($approval->status === SubmissionApprovalStatus::Pending, 422);
        $validated = $request->validate(['decision' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:2000']]);
        if ($validated['decision'] === 'reject' && blank($validated['note'])) {
            return back()->withErrors(['note' => __('institution.participant_submissions.rejection_note_required')]);
        }
        $service->decide($approval, $request->user('institution'), $validated['decision'] === 'approve', $validated['note'] ?? null);

        return redirect()->route('institution.participant-submissions.index')->with('status', __('institution.participant_submissions.decision_saved'));
    }

    private function authorizeInstitution(Request $request, CompetitionSubmissionApproval $approval): void
    {
        $approval->loadMissing('submission.entry.competition');
        abort_unless($approval->approval_type === 'institution' && $approval->submission->entry->competition->institution_id === $request->user('institution')->institution_id, 404);
    }
}
