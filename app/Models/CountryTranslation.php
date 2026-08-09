<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['country_id', 'locale', 'official_name', 'short_name', 'nationality'])]
class CountryTranslation extends Model
{
    use HasUuids;

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
