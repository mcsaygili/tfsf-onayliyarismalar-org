<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_category_id', 'award_reference_id', 'quantity', 'sort_order'])]
class CompetitionCategoryAward extends Model
{
    use HasTranslations, HasUuids;

    protected array $translatedAttributes = ['special_award_text', 'material_award'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'sort_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function awardReference(): BelongsTo
    {
        return $this->belongsTo(AwardReference::class)->withTrashed();
    }

    public function resultAssignments(): HasMany
    {
        return $this->hasMany(CompetitionResultAward::class, 'competition_category_award_id');
    }
}
