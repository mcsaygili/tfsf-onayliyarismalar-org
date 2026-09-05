<?php

namespace App\Models;

use App\Models\Concerns\HasSecurityStamp;
use App\Notifications\Institution\ResetPasswordNotification;
use App\Notifications\Institution\VerifyEmailNotification;
use Database\Factories\InstitutionStaffFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
 * kuruma bağlı (bkz. migration docblock'u). `username` yok — kimlik
 * doğrulama sistem genelinde e-posta üzerinden yürüyor.
 */
#[Fillable(['institution_id', 'email', 'password', 'first_name', 'last_name', 'phone', 'status'])]
#[Hidden(['password', 'remember_token'])]
class InstitutionStaff extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<InstitutionStaffFactory> */
    use HasFactory, HasSecurityStamp, HasUuids, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'boolean',
            'email_verified_at' => 'datetime',
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

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
