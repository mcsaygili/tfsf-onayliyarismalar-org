<?php

namespace App\Models;

use App\Notifications\Institution\ResetPasswordNotification;
use Database\Factories\InstitutionStaffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Kurum personeli — guard: institution. Eski sistemdeki "Kurum Görevlisi" /
 * "Sekreterya" ayrımı kaldırıldı: her personel kaydı zorunlu olarak bir
 * kuruma bağlı (bkz. migration docblock'u).
 */
#[Fillable(['institution_id', 'username', 'email', 'password', 'first_name', 'last_name', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class InstitutionStaff extends Authenticatable
{
    /** @use HasFactory<InstitutionStaffFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
