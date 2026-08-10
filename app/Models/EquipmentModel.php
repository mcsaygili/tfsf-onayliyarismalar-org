<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ekipman Modeli (ör. "EOS R5", "RF 50mm F1.2L USM") — bir Marka'ya ve bir
 * Ekipman Türü'ne bağlı, çeviri gerektirmeyen hiyerarşik referans veri.
 */
#[Fillable(['equipment_brand_id', 'equipment_type_id', 'name', 'status'])]
class EquipmentModel extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(EquipmentBrand::class, 'equipment_brand_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    public function userEquipment(): HasMany
    {
        return $this->hasMany(UserEquipment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
