<?php

namespace App\Models;

use App\Notifications\Eys\ResetPasswordNotification;
use Database\Factories\EysUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * EYS (Elektronik Yönetim Sistemi) kullanıcısı — guard: eys. Diğer
 * gruplardan farklı olarak herkese açık bir kayıt akışı yok: yeni
 * kullanıcılar sadece panel içinden mevcut bir EYS kullanıcısı tarafından
 * oluşturuluyor (bkz. UserController), bu yüzden MustVerifyEmail
 * uygulanmıyor — hesabı oluşturan kullanıcı zaten e-postayı doğruluyor.
 *
 * Sistemde rol/izin alan TEK model — bkz. config/permission.php ve
 * App\Enums\Module (Spatie "team" = modül). Bu yüzden tüm rol/izin
 * kayıtları guard_name = 'eys' taşıyor.
 */
#[Fillable(['email', 'password', 'first_name', 'last_name', 'phone', 'status'])]
#[Hidden(['password', 'remember_token'])]
class EysUser extends Authenticatable
{
    /** @use HasFactory<EysUserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    protected $table = 'eys_users';

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
