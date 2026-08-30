<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use Illuminate\Database\Eloquent\Model;

class CompetitionAuditService
{
    /** @param array<string, mixed> $changes */
    public function record(Competition $competition, string $action, Model $actor, ?string $message = null, array $changes = []): CompetitionStatusLog
    {
        return $competition->statusLogs()->create([
            'action' => $action,
            'from_status' => $competition->status->value,
            'to_status' => $competition->status->value,
            'message' => $message,
            'changes' => $changes,
            'actor_id' => $actor->getKey(),
            'actor_type' => $actor::class,
        ]);
    }
}
