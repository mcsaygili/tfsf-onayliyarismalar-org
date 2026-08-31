<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mail istemcisi ayarları — tekil satır (id=1), ayrı bir yönetim ekranı yok,
 * sadece MailClientController::settings() üzerinden güncellenir.
 */
#[Fillable(['daily_quota', 'rate_per_second', 'enabled', 'updated_by'])]
class MailSetting extends Model
{
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'updated_by');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['enabled' => true]);
    }
}
