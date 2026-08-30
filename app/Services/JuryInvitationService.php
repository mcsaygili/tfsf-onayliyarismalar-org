<?php

namespace App\Services;

use App\Models\Juri;
use App\Models\JuryInvitation;
use App\Notifications\Juri\JuryInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class JuryInvitationService
{
    public function findPendingByToken(string $plainToken): JuryInvitation
    {
        $invitation = JuryInvitation::query()
            ->with(['competition.translations', 'institution'])
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->firstOrFail();

        abort_if($invitation->expires_at === null || $invitation->expires_at->isPast(), 410);

        return $invitation;
    }

    public function send(JuryInvitation $invitation): void
    {
        if (! $invitation->isPending()) {
            return;
        }

        $plainToken = Str::random(64);
        $invitation->forceFill([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays((int) config('auth.jury_invitation.expire_days', 7)),
            'sent_at' => null,
        ])->save();
        $invitation->loadMissing(['competition.translations', 'institution']);

        Notification::route('mail', $invitation->email)
            ->notify(new JuryInvitationNotification($invitation, $plainToken));

        $invitation->forceFill(['sent_at' => now()])->save();
    }

    /** @return array{0: Juri, 1: bool} */
    public function accept(JuryInvitation $invitation, array $attributes): array
    {
        return DB::transaction(function () use ($invitation, $attributes) {
            $lockedInvitation = JuryInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            abort_if(! $lockedInvitation->isPending(), 410);
            abort_if($lockedInvitation->expires_at === null || $lockedInvitation->expires_at->isPast(), 410);

            $juror = Juri::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($lockedInvitation->email)])
                ->first();
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

            $this->linkExistingJuror($juror);

            return [$juror, $created];
        });
    }

    public function linkExistingJuror(Juri $juror): void
    {
        if (! $juror->status || $juror->email_verified_at === null) {
            return;
        }

        JuryInvitation::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($juror->email)])
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->get()
            ->each(function (JuryInvitation $invitation) use ($juror): void {
                $invitation->assignments()->update([
                    'juror_id' => $juror->id,
                    'jury_invitation_id' => null,
                ]);
                $invitation->forceFill([
                    'accepted_juror_id' => $juror->id,
                    'accepted_at' => now(),
                    'token_hash' => null,
                ])->save();
            });
    }
}
