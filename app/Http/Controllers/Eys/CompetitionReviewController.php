<?php

namespace App\Http\Controllers\Eys;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use App\Models\EysUser;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — kurumların yarışma başvurularının incelenmesi
 * (onayla/reddet/ek bilgi talep et), bkz. proje planı "Kurum Paneli —
 * Yarışma Ekleme Sihirbazı & EYS Onay Süreci". Kurum tarafındaki
 * doldurma/gönderme akışı Institution\CompetitionController'da.
 */
class CompetitionReviewController extends Controller
{
    public function index(Request $request): View
    {
        $competitions = Competition::query()
            ->with(['institution', 'translations'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('eys.competitions.index', [
            'competitions' => $competitions,
            'filter' => [
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function show(Competition $competition): View
    {
        $competition->load(['institution', 'institutionStaff', 'competitionType.translations', 'translations', 'statusLogs.actor']);

        return view('eys.competitions.show', [
            'competition' => $competition,
            'steps' => CompetitionStepRegistry::all(),
        ]);
    }

    public function approve(Competition $competition): RedirectResponse
    {
        abort_unless($competition->status === CompetitionStatus::PendingReview, 422);

        DB::transaction(function () use ($competition) {
            $this->recordDecision($competition, CompetitionStatus::Approved, 'approved', extra: [
                'published_at' => now(),
            ]);
        });

        return redirect()->route('eys.competitions.index')->with('status', __('eys.competitions.approved'));
    }

    public function reject(Request $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->status === CompetitionStatus::PendingReview, 422);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($competition, $validated) {
            $this->recordDecision($competition, CompetitionStatus::Rejected, 'rejected', $validated['message']);
        });

        return redirect()->route('eys.competitions.index')->with('status', __('eys.competitions.rejected'));
    }

    public function requestInfo(Request $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->status === CompetitionStatus::PendingReview, 422);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($competition, $validated) {
            $this->recordDecision($competition, CompetitionStatus::NeedsInfo, 'info_requested', $validated['message']);
        });

        return redirect()->route('eys.competitions.index')->with('status', __('eys.competitions.info_requested'));
    }

    /** @param  array<string, mixed>  $extra */
    private function recordDecision(Competition $competition, CompetitionStatus $toStatus, string $action, ?string $message = null, array $extra = []): void
    {
        $fromStatus = $competition->status;

        $competition->forceFill(array_merge([
            'status' => $toStatus,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::guard('eys')->id(),
            'latest_review_message' => $message,
        ], $extra))->save();

        CompetitionStatusLog::create([
            'competition_id' => $competition->id,
            'action' => $action,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'message' => $message,
            'actor_id' => Auth::guard('eys')->id(),
            'actor_type' => EysUser::class,
        ]);
    }
}
