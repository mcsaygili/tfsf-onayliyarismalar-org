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

/** Institution staff and independent, competition-assigned secretariats share one login guard. */
#[Fillable(['institution_id', 'email', 'password', 'first_name', 'last_name', 'phone', 'status'])]
#[Hidden(['password', 'remember_token'])]
class InstitutionStaff extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<InstitutionStaffFactory> */
    use HasFactory, HasSecurityStamp, HasUuids, Notifiable;

    protected $attributes = ['account_kind' => 'institution'];

    public function isSecretariat(): bool
    {
        return $this->account_kind === 'secretariat' && $this->institution_id === null;
    }

    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            if (! in_array($account->account_kind, ['institution', 'secretariat'], true)
                || ($account->account_kind === 'secretariat') !== ($account->institution_id === null)) {
                throw new \LogicException('Account kind and institution ownership must agree.');
            }
            if ($account->exists && $account->isDirty('account_kind')) {
                throw new \LogicException('Account kind conversion requires a dedicated migration.');
            }
        });
    }

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
