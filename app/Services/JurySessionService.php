<?php

namespace App\Services;

use App\Models\CompetitionEvaluationRound;
use App\Models\CompetitionJurySession;
use App\Models\EysUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JurySessionService
{
    public function ensureForRound(CompetitionEvaluationRound $round): CompetitionJurySession
    {
        $jurorIds = $round->competition->categories()
            ->reorder()
            ->join('competition_category_juror_assignments as assignments', 'assignments.competition_category_id', '=', 'competition_categories.id')
            ->whereNotNull('assignments.juror_id')->distinct()->pluck('assignments.juror_id');

        $session = $round->jurySession()->firstOrCreate([], [
            'status' => 'planned',
            'scheduled_at' => $round->opens_at,
            'quorum' => max(1, $jurorIds->count()),
        ]);
        foreach ($jurorIds as $jurorId) {
            $session->attendances()->firstOrCreate(['juror_id' => $jurorId]);
        }

        return $session->load('attendances.juror');
    }

    public function open(CompetitionJurySession $session, EysUser $actor): void
    {
        if ($session->status !== 'planned') {
            throw ValidationException::withMessages(['session' => 'Yalnızca planlanan bir oturum açılabilir.']);
        }
        $session->update(['status' => 'open', 'opened_by' => $actor->id, 'opened_at' => now()]);
    }

    public function close(CompetitionJurySession $session, EysUser $actor): void
    {
        $session->load('attendances');
        if ($session->status !== 'open' || ! $session->hasQuorum()) {
            throw ValidationException::withMessages(['session' => 'Oturum açık olmalı ve katılım yeter sayısı sağlanmalıdır.']);
        }
        if (blank($session->minutes)) {
            throw ValidationException::withMessages(['minutes' => 'Oturum kapatılmadan önce kurul tutanağı girilmelidir.']);
        }
        if ($session->round->committeeDecisions()->where('decision', 'finalist')->exists()) {
            throw ValidationException::withMessages(['session' => 'Tüm finalistler için kurul kararı verilmeden oturum kapatılamaz.']);
        }

        DB::transaction(function () use ($session, $actor): void {
            $session->update(['status' => 'closed', 'closed_by' => $actor->id, 'closed_at' => now()]);
            $session->round->update(['status' => 'finalized', 'finalized_at' => now()]);
        });
    }
}
