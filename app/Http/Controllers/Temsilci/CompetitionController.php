<?php

namespace App\Http\Controllers\Temsilci;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(): View
    {
        $competitions = Auth::guard('temsilci')->user()->competitions()
            ->with(['translations', 'institution'])
            ->withCount(['entries', 'categories', 'monitoringReports'])
            ->orderByRaw('evaluation_ends_at is null')
            ->orderBy('evaluation_ends_at')
            ->paginate(15);

        return view('temsilci.competitions.index', compact('competitions'));
    }

    public function show(Competition $competition): View
    {
        $this->authorizeAssigned($competition);
        $competition->load([
            'translations', 'institution', 'categories.translations', 'monitoringReports.representative',
            'evaluationRounds.evaluationSubmissions', 'evaluationRounds.results',
        ])->loadCount([
            'entries',
            'entries as submitted_entry_count' => fn ($query) => $query->whereNotNull('submitted_at'),
            'entries as pending_approval_count' => fn ($query) => $query->whereHas('submissions.approvals', fn ($approvals) => $approvals->where('status', 'pending')),
        ]);

        return view('temsilci.competitions.show', compact('competition'));
    }

    public function report(Request $request, Competition $competition): RedirectResponse
    {
        $this->authorizeAssigned($competition);
        $validated = $request->validate([
            'status' => ['required', 'in:observation,risk,completed'],
            'subject' => ['required', 'string', 'max:255'],
            'note' => ['required', 'string', 'min:10', 'max:5000'],
            'observed_at' => ['required', 'date'],
        ]);
        $competition->monitoringReports()->create($validated + [
            'representative_id' => Auth::guard('temsilci')->id(),
            'submitted_at' => now(),
        ]);

        return back()->with('status', 'İzleme raporu TFSF kayıtlarına iletildi.');
    }

    private function authorizeAssigned(Competition $competition): void
    {
        abort_unless($competition->representative_id === Auth::guard('temsilci')->id(), 404);
    }
}
