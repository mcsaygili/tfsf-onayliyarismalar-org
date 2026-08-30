<?php

namespace App\Enums;

enum CompetitionOperationalPhase: string
{
    case Unavailable = 'unavailable';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Scheduled = 'scheduled';
    case ApplicationsOpen = 'applications_open';
    case ApplicationsClosed = 'applications_closed';
    case ParticipantApproval = 'participant_approval';
    case EvaluationOpen = 'evaluation_open';
    case EvaluationClosed = 'evaluation_closed';
    case ResultsPublished = 'results_published';
}
