<?php

namespace App\Services;

use App\Enums\CompetitionPublicationState;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetitionPublicationService
{
    public function __construct(private readonly CompetitionAuditService $audit) {}

    public function publish(Competition $competition, Model $actor, ?string $message = null): void
    {
        $this->assertApproved($competition);
        if ($competition->publication_state === CompetitionPublicationState::Cancelled) {
            throw ValidationException::withMessages(['publication' => __('eys.competitions.publication_cancelled_terminal')]);
        }

        $this->changeState($competition, CompetitionPublicationState::Published, 'competition_published', $actor, $message, [
            'published_at' => $competition->published_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);
    }

    public function suspend(Competition $competition, Model $actor, string $reason): void
    {
        $this->assertState($competition, [CompetitionPublicationState::Published]);
        $this->changeState($competition, CompetitionPublicationState::Suspended, 'competition_suspended', $actor, $reason);
    }

    public function resume(Competition $competition, Model $actor, ?string $message = null): void
    {
        $this->assertState($competition, [CompetitionPublicationState::Suspended, CompetitionPublicationState::Unpublished]);
        $this->changeState($competition, CompetitionPublicationState::Published, 'competition_resumed', $actor, $message);
    }

    public function unpublish(Competition $competition, Model $actor, string $reason): void
    {
        $this->assertState($competition, [CompetitionPublicationState::Published, CompetitionPublicationState::Suspended]);
        $this->changeState($competition, CompetitionPublicationState::Unpublished, 'competition_unpublished', $actor, $reason);
    }

    public function cancel(Competition $competition, Model $actor, string $reason): void
    {
        $this->assertApproved($competition);
        if ($competition->publication_state === CompetitionPublicationState::Cancelled) {
            throw ValidationException::withMessages(['publication' => __('eys.competitions.publication_already_cancelled')]);
        }
        $this->changeState($competition, CompetitionPublicationState::Cancelled, 'competition_cancelled', $actor, $reason);
    }

    /** @param array<int, CompetitionPublicationState> $states */
    private function assertState(Competition $competition, array $states): void
    {
        $this->assertApproved($competition);
        if (! in_array($competition->publication_state, $states, true)) {
            throw ValidationException::withMessages(['publication' => __('eys.competitions.publication_invalid_transition')]);
        }
    }

    private function assertApproved(Competition $competition): void
    {
        if ($competition->status !== CompetitionStatus::Approved) {
            throw ValidationException::withMessages(['publication' => __('eys.competitions.publication_requires_approval')]);
        }
    }

    /** @param array<string, mixed> $context */
    private function changeState(Competition $competition, CompetitionPublicationState $state, string $action, Model $actor, ?string $message, array $context = []): void
    {
        DB::transaction(function () use ($competition, $state, $action, $actor, $message, $context): void {
            $from = $competition->publication_state;
            $competition->forceFill([
                'publication_state' => $state,
                'publication_state_changed_at' => now(),
                'published_at' => $competition->published_at ?? now(),
            ])->save();
            $this->audit->record($competition, $action, $actor, $message, [
                'publication_state' => ['from' => $from?->value, 'to' => $state->value],
                ...$context,
            ]);
        });
    }
}
