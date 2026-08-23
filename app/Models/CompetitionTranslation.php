<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_id', 'locale', 'name', 'subject', 'purpose'])]
class CompetitionTranslation extends Model
{
    use HasUuids;

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
