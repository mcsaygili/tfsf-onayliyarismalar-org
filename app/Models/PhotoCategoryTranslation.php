<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['photo_category_id', 'locale', 'name'])]
class PhotoCategoryTranslation extends Model
{
    use HasUuids;

    public function photoCategory(): BelongsTo
    {
        return $this->belongsTo(PhotoCategory::class);
    }
}
