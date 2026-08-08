<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Üye (fotoğrafçı) hesapları — guard: web. Eski sistemdeki tfsf_users +
     * tfsf_users_detail birleştirildi (bkz. docs/MEVCUT-YAPI-ANALIZ.md §5.1).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('email_new')->nullable();
            $table->string('tckimlikno', 11)->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('password');
            // 0 engelli / 1 aktif / 90 aktivasyon bekliyor (eski sistemle aynı numaralandırma
            // — bkz. plan notu: gelecekteki tek seferlik veri aktarımı için düşük maliyetli önlem).
            $table->unsignedTinyInteger('status')->default(1);
            // 0 üye / 1 dernek üyesi / 2 dernek alt üyesi / 3 kayıtlı katılımcı
            $table->unsignedTinyInteger('uye_turu')->default(0);
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number')->nullable();
            // Referans veri modülü (ülke/şehir) sonraki fazda — şimdilik FK yok.
            $table->uuid('country_id')->nullable();
            $table->uuid('city_id')->nullable();
            $table->text('address')->nullable();
            // Bildirim/güvenlik tercihleri (eski tfsf_users_bildirim_ayarlari +
            // tfsf_users_guvenlik_ayarlari) — basit boolean toggle'lar, ayrı tabloya gerek yok.
            $table->json('preferences')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
