<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class CompetitionStateMachine
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_review', 'rejected'],
        'under_review' => ['needs_info', 'waiting_requirements', 'approved', 'rejected'],
        'waiting_requirements' => ['needs_info', 'approved', 'rejected'],
        'needs_info' => ['submitted'],
        'approved' => [],
        'rejected' => [],
    ];

    public function __construct(private readonly CompetitionAuditService $audit) {}

    public function canTransition(CompetitionStatus $from, CompetitionStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true);
    }

    /** @param array<string, mixed> $extra */
    public function transition(
        Competition $competition,
        CompetitionStatus $toStatus,
        string $action,
        Model $actor,
        ?string $message = null,
        array $extra = [],
    ): CompetitionStatusLog {
        return DB::transaction(function () use ($competition, $toStatus, $action, $actor, $message, $extra): CompetitionStatusLog {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $locked->status;

            if (! $this->canTransition($fromStatus, $toStatus)) {
                throw new LogicException("Invalid competition status transition: {$fromStatus->value} -> {$toStatus->value}");
            }

            $locked->forceFill(array_merge([
                'status' => $toStatus,
                'latest_review_message' => $message,
            ], $extra))->save();
            $competition->setRawAttributes($locked->getAttributes(), true)->syncChanges();

            return $this->audit->record(
                $locked,
                $action,
                $actor,
                $message,
                $extra,
                fromStatus: $fromStatus,
                toStatus: $toStatus,
            );
        });
    }
}
