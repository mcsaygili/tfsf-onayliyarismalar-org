<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Temsilci;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompetitionAuditService
{
    /** @param array<string, mixed> $changes */
    public function record(
        Competition $competition,
        string $action,
        Model $actor,
        ?string $message = null,
        array $changes = [],
        ?CompetitionStatus $fromStatus = null,
        ?CompetitionStatus $toStatus = null,
    ): CompetitionStatusLog {
        $request = app()->bound('request') ? app(Request::class) : null;
        $requestId = $request?->attributes->get('audit_request_id')
            ?? $request?->headers->get('X-Request-ID')
            ?? (string) Str::uuid();
        $request?->attributes->set('audit_request_id', $requestId);

        return $competition->statusLogs()->create([
            'action' => $action,
            'from_status' => ($fromStatus ?? $competition->status)->value,
            'to_status' => ($toStatus ?? $competition->status)->value,
            'message' => $message,
            'changes' => $changes,
            'actor_id' => $actor->getKey(),
            'actor_type' => $actor::class,
            'actor_guard' => $this->guardFor($actor),
            'request_id' => $requestId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    private function guardFor(Model $actor): string
    {
        return match (true) {
            $actor instanceof EysUser => 'eys',
            $actor instanceof InstitutionStaff => 'institution',
            $actor instanceof Temsilci => 'temsilci',
            $actor instanceof Juri => 'juri',
            $actor instanceof User => 'web',
            default => 'system',
        };
    }
}
