<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'reason', 'starts_at', 'ends_at', 'created_by', 'lifted_at', 'lifted_by', 'lift_reason'])]
class MemberRestriction extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'lifted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'created_by');
    }

    public function lifter(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'lifted_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('lifted_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
