<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['regulation_item_id', 'locale', 'content'])]
class RegulationItemTranslation extends Model
{
    use HasUuids;

    public function regulationItem(): BelongsTo
    {
        return $this->belongsTo(RegulationItem::class);
    }
}
