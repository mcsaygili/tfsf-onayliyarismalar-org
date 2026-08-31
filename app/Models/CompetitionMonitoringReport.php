<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_id', 'representative_id', 'status', 'subject', 'note', 'observed_at', 'submitted_at'])]
class CompetitionMonitoringReport extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['observed_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Temsilci::class, 'representative_id');
    }
}
