<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['regulation_section_id', 'locale', 'name'])]
class RegulationSectionTranslation extends Model
{
    use HasUuids;

    public function regulationSection(): BelongsTo
    {
        return $this->belongsTo(RegulationSection::class);
    }
}
