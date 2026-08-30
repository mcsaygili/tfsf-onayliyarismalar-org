<?php

namespace App\Enums;

enum CommitteeDecisionStatus: string
{
    case Finalist = 'finalist';
    case Selected = 'selected';
    case NotSelected = 'not_selected';
}
