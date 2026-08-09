<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['city_id', 'locale', 'official_name'])]
class CityTranslation extends Model
{
    use HasUuids;

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
