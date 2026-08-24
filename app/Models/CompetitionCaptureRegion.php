<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_id', 'country_id', 'city_id', 'sort_order'])]
class CompetitionCaptureRegion extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withTrashed();
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class)->withTrashed();
    }
}
