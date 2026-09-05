<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'type', 'reason', 'starts_at', 'ends_at', 'created_by', 'lifted_at', 'lifted_by', 'lift_reason'])]
class MemberRestriction extends Model
{
    use HasUuids;

    public function save(array $options = [])
    {
        $ids = [];
        $changed = ! $this->exists || $this->isDirty(['user_id', 'type', 'starts_at', 'ends_at', 'lifted_at']);
        if ($changed && $this->type === 'account') {
            $ids[] = $this->user_id;
        }
        if ($changed && $this->exists && $this->getRawOriginal('type') === 'account') {
            $ids[] = $this->getRawOriginal('user_id');
        }

        // Do not replay an already-mutated Eloquent instance after a failed write.
        return $this->getConnection()->transaction(function () use ($options, $ids) {
            $users = User::whereIn('id', array_unique($ids))->orderBy('id')->lockForUpdate()->get();
            $saved = parent::save($options);
            if ($saved) {
                foreach ($users as $user) {
                    $this->revokeAccount($user);
                }
            }

            return $saved;
        });
    }

    public function delete()
    {
        return $this->getConnection()->transaction(function () {
            $user = $this->type === 'account' ? User::whereKey($this->user_id)->lockForUpdate()->first() : null;
            $deleted = parent::delete();
            if ($deleted && $user) {
                $this->revokeAccount($user);
            }

            return $deleted;
        });
    }

    private function revokeAccount(User $user): void
    {
        $user->forceFill(['security_stamp' => Str::random(64), 'remember_token' => null, 'remember_context' => null])->save();
    }

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
