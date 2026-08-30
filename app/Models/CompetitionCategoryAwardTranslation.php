<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_category_award_id', 'locale', 'special_award_text', 'material_award'])]
class CompetitionCategoryAwardTranslation extends Model
{
    use HasUuids;

    public function categoryAward(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategoryAward::class, 'competition_category_award_id');
    }
}
