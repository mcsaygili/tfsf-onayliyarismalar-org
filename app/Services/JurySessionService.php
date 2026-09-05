<?php

namespace App\Services;

use App\Enums\EvaluationRoundMethod;
use App\Models\Competition;
use App\Models\CompetitionEvaluationRound;
use App\Models\CompetitionJurySession;
use App\Models\EysUser;
use App\Models\Juri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JurySessionService
{
    public function ensureForRound(CompetitionEvaluationRound $round): CompetitionJurySession
    {
        return DB::transaction(function () use ($round) {
            CompetitionMutationLock::acquire($round->competition_id);
            $round = $round->fresh();
            $jurorIds = $round->competition->categories()->reorder()
                ->join('competition_category_juror_assignments as assignments', 'assignments.competition_category_id', '=', 'competition_categories.id')
                ->whereNotNull('assignments.juror_id')->distinct()->pluck('assignments.juror_id');
            $session = $round->jurySession()->firstOrCreate([], ['status' => 'planned', 'scheduled_at' => $round->opens_at, 'quorum' => max(1, $jurorIds->count())]);
            if ($session->status === 'planned') {
                $changed = false;
                foreach ($jurorIds as $jurorId) {
                    $changed = $session->attendances()->firstOrCreate(['juror_id' => $jurorId])->wasRecentlyCreated || $changed;
                }
                if ($changed) {
                    $session->increment('version');
                }
            }

            return $session->load('attendances.juror');
        });
    }

    private function editableSession(Competition $competition, int $version): CompetitionJurySession
    {
        $round = $competition->evaluationRounds()->where('is_final', true)->where('method', EvaluationRoundMethod::Committee->value)->firstOrFail();
        $session = $round->jurySession()->firstOrFail();
        if ($competition->results_published_at || ! in_array($session->status, ['planned', 'open'], true)) {
            throw ValidationException::withMessages(['session' => __('jury_session.locked')]);
        }
        if ($session->version !== $version) {
            throw ValidationException::withMessages(['session' => __('jury_session.stale')]);
        }

        return $session;
    }

    public function update(Competition $competition, EysUser $actor, array $input): void
    {
        $data = Validator::make($input, [
            'session_version' => ['required', 'integer', 'min:0'],
            'scheduled_at' => ['nullable', 'date'], 'location' => ['nullable', 'string', 'max:255'],
            'quorum' => ['required', 'integer', 'min:1', 'max:30'], 'minutes' => ['nullable', 'string', 'max:10000'],
            'attendances' => ['nullable', 'array'], 'attendances.*' => ['required', Rule::in(['invited', 'present', 'absent'])],
            'action' => ['required', Rule::in(['save', 'open', 'close'])],
        ])->validate();
        DB::transaction(function () use ($competition, $actor, $data) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            $session = $this->editableSession($competition, (int) $data['session_version']);
            $ids = $session->attendances()->pluck('id')->all();
            if (array_diff(array_keys($data['attendances'] ?? []), $ids)) {
                throw ValidationException::withMessages(['session' => __('jury_session.invalid_attendance')]);
            }
            $session->update(collect($data)->only(['scheduled_at', 'location', 'quorum', 'minutes'])->all());
            foreach ($data['attendances'] ?? [] as $id => $status) {
                $session->attendances()->whereKey($id)->update(['attendance_status' => $status]);
            }
            if ($data['action'] === 'open') {
                if ($session->status !== 'planned') {
                    throw ValidationException::withMessages(['session' => __('jury_session.open_planned')]);
                }
                $session->update(['status' => 'open', 'opened_by' => $actor->id, 'opened_at' => now()]);
            } elseif ($data['action'] === 'close') {
                if ($session->status !== 'open' || ! $session->hasQuorum()) {
                    throw ValidationException::withMessages(['session' => __('jury_session.quorum')]);
                }
                if (blank($session->minutes)) {
                    throw ValidationException::withMessages(['minutes' => __('jury_session.minutes')]);
                }
                if ($session->round->committeeDecisions()->where('decision', 'finalist')->exists()) {
                    throw ValidationException::withMessages(['session' => __('jury_session.undecided')]);
                }
                $session->update(['status' => 'closed', 'closed_by' => $actor->id, 'closed_at' => now()]);
                $session->round->update(['status' => 'finalized', 'finalized_at' => now()]);
            }
            $session->increment('version');
            app(CompetitionAuditService::class)->record($competition, 'jury_session_'.$data['action'], $actor,
                changes: ['session_id' => $session->id, 'status' => $session->status, 'quorum' => $session->quorum, 'version' => $session->version]);
        });
    }

    public function declare(Competition $competition, Juri $actor, array $input): void
    {
        $data = Validator::make($input, ['session_version' => ['required', 'integer', 'min:0'],
            'conflict_declared' => ['required', 'boolean'], 'conflict_note' => ['nullable', Rule::requiredIf(fn () => filter_var($input['conflict_declared'] ?? false, FILTER_VALIDATE_BOOLEAN)), 'string', 'min:10', 'max:2000']])->validate();
        DB::transaction(function () use ($competition, $actor, $data) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            abort_unless(Juri::whereKey($actor->id)->where('status', true)->whereHas('categoryAssignments.category', fn ($query) => $query->where('competition_id', $competition->id))->exists(), 404);
            $session = $this->editableSession($competition, (int) $data['session_version']);
            $attendance = $session->attendances()->where('juror_id', $actor->id)->firstOrFail();
            $attendance->update(['conflict_declared' => (bool) $data['conflict_declared'],
                'conflict_note' => $data['conflict_declared'] ? ($data['conflict_note'] ?? null) : null, 'declared_at' => now()]);
            $session->increment('version');
            app(CompetitionAuditService::class)->record($competition, 'jury_session_declaration', $actor,
                changes: ['session_id' => $session->id, 'attendance_id' => $attendance->id, 'conflict_declared' => $attendance->conflict_declared, 'version' => $session->version]);
        });
    }
}
