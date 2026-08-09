<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['country_id', 'status'])]
class City extends Model
{
    use HasTranslations, HasUuids, SoftDeletes;

    /** @var array<int, string> */
    protected array $translatedAttributes = ['official_name'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
