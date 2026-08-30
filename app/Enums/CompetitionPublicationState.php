<?php

namespace App\Enums;

enum CompetitionPublicationState: string
{
    case Unpublished = 'unpublished';
    case Published = 'published';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }
}
