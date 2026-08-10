<?php

namespace App\Models;

use App\Notifications\Uye\ResetPasswordNotification;
use App\Notifications\Uye\VerifyEmailNotification;
use Database\Factories\UserFactory;
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
 * Üye (fotoğrafçı) — guard: web. TFSF'nin herkese açık, kendi kaydını
 * oluşturan "varsayılan" kimliği; bu yüzden framework konvansiyonuyla
 * (App\Models\User, web guard) uyumlu tutuluyor — diğer 3 grup (Kurum,
 * Temsilci, Juri) ayrı isimlendirilmiş modeller/guard'lar kullanıyor.
 *
 * TODO(EYS): Bu tablo, ileride Administrator/EYS modülü tarafından
 * yönetilecek (üye yasaklama, dernek bağlantısı vb.) — bu fazda yok.
 */
#[Fillable(['username', 'email', 'first_name', 'last_name', 'password', 'gender', 'date_of_birth', 'phone_number', 'country_id', 'city_id', 'address', 'status', 'uye_turu', 'education_level_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'integer',
            'uye_turu' => 'integer',
            'date_of_birth' => 'date',
            'preferences' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function canUploadMorePhotos(): bool
    {
        return $this->photos()->count() < PortfolioSetting::current()->max_photos_per_user;
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
