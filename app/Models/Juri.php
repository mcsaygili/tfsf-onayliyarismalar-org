<?php

namespace App\Models;

use App\Notifications\Juri\ResetPasswordNotification;
use Database\Factories\JuriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Jüri üyesi — guard: juri.
 */
#[Fillable(['username', 'email', 'password', 'first_name', 'last_name', 'phone', 'tckimlikno'])]
#[Hidden(['password', 'remember_token'])]
class Juri extends Authenticatable
{
    /** @use HasFactory<JuriFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'juriler';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
