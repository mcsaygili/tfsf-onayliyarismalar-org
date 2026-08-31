<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Juri;
use App\Models\JuryInvitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class JuryInvitationService
{
    public function findPendingByToken(string $plainToken): JuryInvitation
    {
        $invitation = JuryInvitation::query()
            ->with(['competition.translations', 'institution'])
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('revoked_at')
            ->firstOrFail();

        abort_if($invitation->expires_at === null || $invitation->expires_at->isPast(), 410);

        return $invitation;
    }

    public function existingJurorFor(JuryInvitation $invitation): ?Juri
    {
        return Juri::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($invitation->email)])
            ->first();
    }

    public function send(JuryInvitation $invitation, ?Model $actor = null): void
    {
        if (! $invitation->isPending()) {
            return;
        }

        $action = $invitation->send_count > 0 ? 'resent' : 'sent';
        $plainToken = Str::random(64);
        $invitation->forceFill([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays((int) config('auth.jury_invitation.expire_days', 7)),
            'sent_at' => null,
            'opened_at' => null,
            'send_count' => $invitation->send_count + 1,
        ])->save();
        $invitation->loadMissing(['competition.translations', 'institution']);

        app(NotificationDispatchService::class)->queueJuryInvitation($invitation, $plainToken);

        $invitation->forceFill(['sent_at' => now()])->save();
        $this->recordEvent($invitation, $action, $actor, [
            'send_count' => $invitation->send_count,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
        ]);
    }

    public function markOpened(JuryInvitation $invitation): void
    {
        if (! $invitation->isPending() || $invitation->opened_at !== null) {
            return;
        }

        $invitation->forceFill(['opened_at' => now()])->save();
        $this->recordEvent($invitation, 'opened');
    }

    /** @return array{0: Juri, 1: bool} */
    public function accept(JuryInvitation $invitation, array $attributes): array
    {
        return DB::transaction(function () use ($invitation, $attributes) {
            $lockedInvitation = JuryInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            abort_if(! $lockedInvitation->isPending(), 410);
            abort_if($lockedInvitation->expires_at === null || $lockedInvitation->expires_at->isPast(), 410);

            $juror = $this->existingJurorFor($lockedInvitation);
            $created = false;

            abort_if(
                $juror && (! $juror->status || $juror->email_verified_at === null),
                422,
                __('juri.invitation.account_unavailable')
            );

            if (! $juror) {
                $juror = Juri::create([
                    'email' => $lockedInvitation->email,
                    'password' => Hash::make($attributes['password']),
                    'first_name' => $attributes['first_name'],
                    'last_name' => $attributes['last_name'],
                    'status' => true,
                    'registration_source' => 'institution_invitation',
                ]);
                $juror->forceFill(['email_verified_at' => now()])->save();
                $created = true;
            }

            $this->linkExistingJuror($juror, $juror);

            return [$juror, $created];
        });
    }

    public function decline(JuryInvitation $invitation): void
    {
        DB::transaction(function () use ($invitation): void {
            $lockedInvitation = JuryInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            abort_if(! $lockedInvitation->isPending(), 410);
            abort_if($lockedInvitation->expires_at === null || $lockedInvitation->expires_at->isPast(), 410);

            $lockedInvitation->forceFill([
                'declined_at' => now(),
                'token_hash' => null,
                'expires_at' => null,
            ])->save();
            $this->recordEvent($lockedInvitation, 'declined');
        });
    }

    public function cancel(JuryInvitation $invitation, Model $actor): void
    {
        DB::transaction(function () use ($invitation, $actor): void {
            $lockedInvitation = JuryInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            abort_unless($lockedInvitation->canCancel(), 422);

            $assignmentCount = $lockedInvitation->assignments()->count();
            $lockedInvitation->assignments()->delete();
            $lockedInvitation->forceFill([
                'revoked_at' => now(),
                'token_hash' => null,
                'expires_at' => null,
            ])->save();
            $this->recordEvent($lockedInvitation, 'cancelled', $actor, [
                'removed_assignment_count' => $assignmentCount,
            ]);
        });
    }

    public function linkExistingJuror(Juri $juror, ?Model $actor = null): void
    {
        if (! $juror->status || $juror->email_verified_at === null) {
            return;
        }

        $competitionIds = DB::transaction(function () use ($juror, $actor) {
            return JuryInvitation::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($juror->email)])
                ->whereNull('accepted_at')
                ->whereNull('declined_at')
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get()
                ->map(function (JuryInvitation $invitation) use ($juror, $actor): string {
                    $invitation->assignments()->update([
                        'juror_id' => $juror->id,
                        'jury_invitation_id' => null,
                    ]);
                    $invitation->forceFill([
                        'accepted_juror_id' => $juror->id,
                        'accepted_at' => now(),
                        'token_hash' => null,
                        'expires_at' => null,
                    ])->save();
                    $this->recordEvent($invitation, 'accepted', $actor ?? $juror, [
                        'juror_id' => $juror->id,
                    ]);

                    return $invitation->competition_id;
                })
                ->unique()
                ->values();
        });

        Competition::query()->whereKey($competitionIds)->get()->each(
            fn (Competition $competition) => $this->recordJuryRequirementsCompleted($competition, $actor ?? $juror)
        );
    }

    private function recordEvent(JuryInvitation $invitation, string $action, ?Model $actor = null, array $metadata = []): void
    {
        $invitation->events()->create([
            'action' => $action,
            'actor_id' => $actor?->getKey(),
            'actor_type' => $actor ? $actor::class : null,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function recordJuryRequirementsCompleted(Competition $competition, Model $actor): void
    {
        if ($competition->status !== CompetitionStatus::WaitingRequirements
            || ! app(CompetitionReadinessService::class)->allJurorsRegistered($competition)
            || $competition->statusLogs()->where('action', 'jury_requirements_completed')->exists()) {
            return;
        }

        app(CompetitionAuditService::class)->record(
            $competition,
            'jury_requirements_completed',
            $actor,
        );
    }
}
