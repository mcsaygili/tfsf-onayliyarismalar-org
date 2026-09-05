<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['competition_id', 'actor_type', 'actor_id', 'active', 'version', 'reason', 'updated_by'])]
class RegistrationExceptionGrant extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'version' => 'integer'];
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}
