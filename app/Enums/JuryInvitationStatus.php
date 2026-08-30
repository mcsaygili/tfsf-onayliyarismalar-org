<?php

namespace App\Enums;

enum JuryInvitationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Opened = 'opened';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'is-draft',
            self::Sent => 'is-pending',
            self::Opened => 'is-under-review',
            self::Accepted => 'is-approved',
            self::Declined, self::Cancelled => 'is-rejected',
            self::Expired => 'is-waiting-requirements',
        };
    }
}
