<?php

namespace App\Models;

use App\Models\Concerns\HasSecurityStamp;
use App\Notifications\Juri\ResetPasswordNotification;
use App\Notifications\Juri\VerifyEmailNotification;
use Database\Factories\JuriFactory;
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
 * Jüri üyesi — guard: juri. Yeni hesaplar yalnızca kurum davetiyle veya
 * EYS yöneticisi tarafından oluşturulur; herkese açık kayıt yoktur.
 */
#[Fillable(['email', 'password', 'first_name', 'last_name', 'phone', 'tckimlikno', 'status', 'registration_source', 'education_level_id'])]
#[Hidden(['password', 'remember_token'])]
class Juri extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<JuriFactory> */
    use HasFactory, HasSecurityStamp, HasUuids, Notifiable;

    protected $table = 'jurors';

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

    public function categoryAssignments(): HasMany
    {
        return $this->hasMany(CompetitionCategoryJurorAssignment::class, 'juror_id');
    }

    public function acceptedInvitations(): HasMany
    {
        return $this->hasMany(JuryInvitation::class, 'accepted_juror_id');
    }

    public function sessionAttendances(): HasMany
    {
        return $this->hasMany(CompetitionJurySessionAttendance::class, 'juror_id');
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
