<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uye (web guard) şifre sıfırlama token'ları. Diğer 3 grubun kendi ayrı
     * tablosu var — bkz. docs plan notu: paylaşımlı tek tablo, aynı e-posta
     * birden fazla grupta varsa bir guard'ın token'ının başka guard'ı
     * sıfırlamasına izin verebilir.
     */
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
