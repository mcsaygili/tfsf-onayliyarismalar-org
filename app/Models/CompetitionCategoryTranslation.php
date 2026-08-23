<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['competition_category_id', 'locale', 'name'])]
class CompetitionCategoryTranslation extends Model
{
    use HasUuids;
}
