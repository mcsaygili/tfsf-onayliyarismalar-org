<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['participant_approval_process_id', 'locale', 'name', 'description'])]
class ParticipantApprovalProcessTranslation extends Model
{
    use HasUuids;
}
