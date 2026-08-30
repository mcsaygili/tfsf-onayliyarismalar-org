<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['award_reference_id', 'locale', 'name', 'description'])]
class AwardReferenceTranslation extends Model
{
    use HasUuids;

    public function awardReference(): BelongsTo
    {
        return $this->belongsTo(AwardReference::class);
    }
}
