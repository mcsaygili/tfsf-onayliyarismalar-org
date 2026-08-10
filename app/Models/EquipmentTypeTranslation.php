<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['equipment_type_id', 'locale', 'name'])]
class EquipmentTypeTranslation extends Model
{
    use HasUuids;

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }
}
