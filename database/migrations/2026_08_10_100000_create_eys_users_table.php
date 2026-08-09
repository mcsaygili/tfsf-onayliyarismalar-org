<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EYS (Elektronik Yönetim Sistemi) — sistemi yöneten kullanıcılar,
 * guard: eys. Diğer gruplardan farklı olarak herkese açık bir kayıt akışı
 * YOK: yeni EYS kullanıcıları sadece panel içinden mevcut bir kullanıcı
 * tarafından oluşturuluyor (bkz. UserController), bu yüzden e-posta
 * doğrulaması da (email_verified_at) gerekmiyor — hesabı oluşturan
 * kullanıcı zaten e-postayı doğruluyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eys_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('status')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eys_users');
    }
};
