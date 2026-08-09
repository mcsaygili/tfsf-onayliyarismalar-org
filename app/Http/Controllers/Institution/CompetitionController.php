<?php

namespace App\Http\Controllers\Institution;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kurumun yarışma başvurularının listesi + yeni taslak başlatma + onaya
 * gönderme (bkz. proje planı "Kurum Paneli — Yarışma Ekleme Sihirbazı").
 * Adım adım doldurma CompetitionStepController'da.
 */
class CompetitionController extends Controller
{
    public function index(): View
    {
        $institution = Auth::guard('institution')->user()->institution;

        return view('institution.competitions.index', [
            'competitions' => $institution->competitions()->latest()->paginate(10),
        ]);
    }

    public function store(): RedirectResponse
    {
        $staff = Auth::guard('institution')->user();

        $competition = $staff->institution->competitions()->create([
            'institution_staff_id' => $staff->id,
        ]);

        return redirect()->route('institution.competitions.step.show', [$competition, 1]);
    }

    public function submit(Competition $competition): RedirectResponse
    {
        $this->authorizeSameInstitution($competition);

        abort_unless($competition->isEditable(), 403);
        abort_unless($competition->canSubmit(), 422, __('institution.competitions.cannot_submit_incomplete'));

        $fromStatus = $competition->status;

        DB::transaction(function () use ($competition, $fromStatus) {
            $competition->forceFill([
                'status' => CompetitionStatus::PendingReview,
                'submitted_at' => now(),
            ])->save();

            CompetitionStatusLog::create([
                'competition_id' => $competition->id,
                'action' => $fromStatus === CompetitionStatus::NeedsInfo ? 'resubmitted' : 'submitted',
                'from_status' => $fromStatus->value,
                'to_status' => CompetitionStatus::PendingReview->value,
                'actor_id' => Auth::guard('institution')->id(),
                'actor_type' => Auth::guard('institution')->user()::class,
            ]);
        });

        return redirect()->route('institution.competitions.index')->with('status', __('institution.competitions.submitted'));
    }

    private function authorizeSameInstitution(Competition $competition): void
    {
        if ($competition->institution_id !== Auth::guard('institution')->user()->institution_id) {
            throw new AuthorizationException;
        }
    }
}
