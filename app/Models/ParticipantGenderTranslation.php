<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['participant_gender_id', 'locale', 'name', 'description'])]
class ParticipantGenderTranslation extends Model
{
    use HasUuids;
}
