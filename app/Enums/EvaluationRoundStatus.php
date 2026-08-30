<?php

namespace App\Enums;

enum EvaluationRoundStatus: string
{
    case Planned = 'planned';
    case Open = 'open';
    case Closed = 'closed';
    case Finalized = 'finalized';
}
