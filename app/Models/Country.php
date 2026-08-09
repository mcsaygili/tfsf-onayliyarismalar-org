<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['iso_alpha2', 'iso_alpha3', 'status'])]
class Country extends Model
{
    use HasTranslations, HasUuids, SoftDeletes;

    /** @var array<int, string> */
    protected array $translatedAttributes = ['official_name', 'short_name', 'nationality'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
