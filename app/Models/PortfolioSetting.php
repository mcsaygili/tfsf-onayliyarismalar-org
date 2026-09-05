<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Üye fotoğraf portfolyosu ayarları — tekil satır (id=1), ayrı bir listeleme
 * ekranı yok, sadece SystemSettingsController::portfolio() üzerinden
 * güncellenir (bkz. MailSetting — aynı desen).
 */
#[Fillable(['max_photos_per_user', 'updated_by'])]
class PortfolioSetting extends Model
{
    /**
     * Satır ilk oluşturulurken kullanılan varsayılan — migration'daki sütun
     * varsayılanıyla aynı tutulmalı. firstOrCreate() ile açıkça verilmezse,
     * bellekteki model örneği DB'nin uyguladığı sütun varsayılanını
     * YANSITMAZ (Eloquent INSERT sonrası satırı yeniden çekmez) — bu yüzden
     * burada tekrar belirtilmesi gerekiyor.
     */
    private const DEFAULT_MAX_PHOTOS_PER_USER = 30;

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'updated_by');
    }

    public static function current(): self
    {
        // Only this internally supplied identity bypasses mass-assignment rules.
        return static::unguarded(fn () => static::query()->firstOrCreate(['id' => 1], ['max_photos_per_user' => self::DEFAULT_MAX_PHOTOS_PER_USER]));
    }
}
