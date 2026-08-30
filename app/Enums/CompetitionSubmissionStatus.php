<?php

namespace App\Enums;

enum CompetitionSubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Disqualified = 'disqualified';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
