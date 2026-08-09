<?php

use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Temsilci;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | 4 grup (Uye/Institution/Temsilci/Juri) — her biri ayrı guard + ayrı
    | provider. Cookie ismi izolasyonu burada değil, App\Http\Middleware\
    | ResolveGuardSessionCookie'de sağlanıyor (bkz. o sınıfın docblock'u).
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'institution' => [
            'driver' => 'session',
            'provider' => 'institution_staff',
        ],

        'temsilci' => [
            'driver' => 'session',
            'provider' => 'representatives',
        ],

        'juri' => [
            'driver' => 'session',
            'provider' => 'jurors',
        ],

        'eys' => [
            'driver' => 'session',
            'provider' => 'eys_users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        'institution_staff' => [
            'driver' => 'eloquent',
            'model' => InstitutionStaff::class,
        ],

        'representatives' => [
            'driver' => 'eloquent',
            'model' => Temsilci::class,
        ],

        'jurors' => [
            'driver' => 'eloquent',
            'model' => Juri::class,
        ],

        'eys_users' => [
            'driver' => 'eloquent',
            'model' => EysUser::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Guard başına AYRI token tablosu: Laravel'in password broker'ı token'ı
    | sadece e-posta ile eşliyor, provider ayrımı yapmıyor. Aynı e-posta
    | birden fazla grupta varsa (mümkün, gruplar tamamen bağımsız), tek bir
    | paylaşımlı tablo bir guard'ın token'ının başka guard'ı sıfırlamasına
    | izin verebilirdi — ayrı tablolar bunu yapısal olarak imkansız kılıyor.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'institution' => [
            'provider' => 'institution_staff',
            'table' => 'institution_staff_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'temsilci' => [
            'provider' => 'representatives',
            'table' => 'representative_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'juri' => [
            'provider' => 'jurors',
            'table' => 'juror_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'eys' => [
            'provider' => 'eys_users',
            'table' => 'eys_users_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
