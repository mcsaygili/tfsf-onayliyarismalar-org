<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Marka (Canon/Nikon/Sigma/vb.) — kod tabanındaki ilk çeviri gerektirmeyen
 * referans veri modeli. `name` düz bir Eloquent attribute'u, HasTranslations
 * kullanılmıyor (marka adları dilden bağımsız).
 */
#[Fillable(['name', 'status'])]
class EquipmentBrand extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function models(): HasMany
    {
        return $this->hasMany(EquipmentModel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
