<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diğer tüm modüllerde olduğu gibi (bkz. institutions/institution_staff)
 * veritabanı tablo isimlerinde Türkçe kullanılmıyor. `username` de sistem
 * genelindeki "kimlik doğrulama sadece e-posta üzerinden" politikası
 * gereği kaldırılıyor — kayıt artık Institution'daki gibi sadece
 * e-posta+şifre ile başlıyor, ad/soyad girişten sonra tamamlanıyor.
 *
 * `username` unique index'i, tablo hâlâ `temsilciler` adındayken (rename'den
 * ÖNCE) açıkça düşürülüyor — SQLite (test ortamı), rename sonrası eski
 * index adını referans alan bir dropColumn'da tablo yeniden oluşturma
 * adımını başarısız kılıyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temsilciler', function (Blueprint $table) {
            $table->dropUnique('temsilciler_username_unique');
        });

        Schema::table('temsilciler', function (Blueprint $table) {
            $table->dropColumn('username');
        });

        Schema::rename('temsilciler', 'representatives');

        Schema::table('representatives', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        Schema::table('representatives', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('representatives', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });

        Schema::table('representatives', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });

        Schema::rename('representatives', 'temsilciler');

        Schema::table('temsilciler', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
        });
    }
};
