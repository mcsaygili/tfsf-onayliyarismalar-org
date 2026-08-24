<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['competition_id', 'regulation_item_id', 'locale', 'content'])]
class CompetitionRegulationInput extends Model
{
    use HasUuids;
}
