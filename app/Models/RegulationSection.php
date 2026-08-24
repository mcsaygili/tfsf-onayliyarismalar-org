<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'sort_order', 'status', 'is_system', 'version'])]
class RegulationSection extends Model
{
    use HasTranslations, HasUuids, SoftDeletes;

    /** @var array<int, string> */
    protected array $translatedAttributes = ['name'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RegulationItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
