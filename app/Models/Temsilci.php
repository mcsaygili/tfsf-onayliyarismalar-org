<?php

namespace App\Models;

use App\Notifications\Temsilci\ResetPasswordNotification;
use App\Notifications\Temsilci\VerifyEmailNotification;
use Database\Factories\TemsilciFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * TFSF bölge temsilcisi — guard: temsilci. Kayıt Institution'daki gibi
 * sadece e-posta+şifre ile başlıyor (bkz. Auth\RegisteredController),
 * ad/soyad girişten sonra Temsilci Bilgileri sayfasında tamamlanıyor.
 * city_id/region_id/dernek_bilgi_id, referans veri modülü gelene kadar
 * bilinçli olarak Fillable dışında (bkz. migration docblock'u).
 */
#[Fillable(['email', 'password', 'first_name', 'last_name', 'phone', 'status', 'education_level_id'])]
#[Hidden(['password', 'remember_token'])]
class Temsilci extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<TemsilciFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'representatives';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class, 'representative_id');
    }

    public function monitoringReports(): HasMany
    {
        return $this->hasMany(CompetitionMonitoringReport::class, 'representative_id');
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
