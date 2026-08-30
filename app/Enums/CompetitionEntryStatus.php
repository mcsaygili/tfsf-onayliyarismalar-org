<?php

namespace App\Enums;

enum CompetitionEntryStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
