<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['institution_type_id', 'locale', 'name'])]
class InstitutionTypeTranslation extends Model
{
    use HasUuids;

    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }
}
