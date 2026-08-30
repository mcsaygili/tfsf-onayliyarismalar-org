<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class CompetitionWorkflowService
{
    /** @param array<string, mixed> $extra */
    public function transition(
        Competition $competition,
        CompetitionStatus $toStatus,
        string $action,
        Model $actor,
        ?string $message = null,
        array $extra = [],
    ): CompetitionStatusLog {
        $fromStatus = $competition->status;

        if (! $fromStatus->canTransitionTo($toStatus)) {
            throw new LogicException("Invalid competition status transition: {$fromStatus->value} -> {$toStatus->value}");
        }

        $competition->forceFill(array_merge([
            'status' => $toStatus,
            'latest_review_message' => $message,
        ], $extra))->save();

        return CompetitionStatusLog::create([
            'competition_id' => $competition->id,
            'action' => $action,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'message' => $message,
            'actor_id' => $actor->getKey(),
            'actor_type' => $actor::class,
        ]);
    }
}
