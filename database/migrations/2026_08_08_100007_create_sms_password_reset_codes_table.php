<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uye'nin SMS ile şifre sıfırlama akışı için — Laravel'in e-posta bazlı
     * password_reset_tokens tablosunun telefon-anahtarlı küçük karşılığı.
     */
    public function up(): void
    {
        Schema::create('sms_password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_password_reset_codes');
    }
};
