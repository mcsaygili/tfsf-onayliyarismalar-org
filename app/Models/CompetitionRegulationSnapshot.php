<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['competition_id', 'version', 'content', 'compiled_at'])]
class CompetitionRegulationSnapshot extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['version' => 'integer', 'content' => 'array', 'compiled_at' => 'datetime'];
    }
}
