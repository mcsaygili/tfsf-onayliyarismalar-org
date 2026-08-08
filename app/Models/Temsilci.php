<?php

namespace App\Models;

use App\Notifications\Temsilci\ResetPasswordNotification;
use Database\Factories\TemsilciFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * TFSF bölge temsilcisi — guard: temsilci.
 */
#[Fillable(['username', 'email', 'password', 'first_name', 'last_name', 'phone', 'city_id', 'region_id', 'dernek_bilgi_id'])]
#[Hidden(['password', 'remember_token'])]
class Temsilci extends Authenticatable
{
    /** @use HasFactory<TemsilciFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'temsilciler';

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
