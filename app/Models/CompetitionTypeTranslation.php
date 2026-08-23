<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_type_id', 'locale', 'name', 'description'])]
class CompetitionTypeTranslation extends Model
{
    use HasUuids;

    public function competitionType(): BelongsTo
    {
        return $this->belongsTo(CompetitionType::class);
    }
}
