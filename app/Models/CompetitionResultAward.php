<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_photo_result_id', 'competition_category_award_id', 'slot_number', 'assigned_by'])]
class CompetitionResultAward extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['slot_number' => 'integer'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(CompetitionPhotoResult::class, 'competition_photo_result_id');
    }

    public function categoryAward(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategoryAward::class, 'competition_category_award_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'assigned_by');
    }
}
