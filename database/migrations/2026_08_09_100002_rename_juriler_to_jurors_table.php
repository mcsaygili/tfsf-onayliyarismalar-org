<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bkz. rename_temsilciler_to_representatives_table — aynı gerekçe ve aynı
 * SQLite uyumluluk sırası (index'i rename'den önce, hâlâ eski tablo
 * adındayken düşür).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('juriler', function (Blueprint $table) {
            $table->dropUnique('juriler_username_unique');
        });

        Schema::table('juriler', function (Blueprint $table) {
            $table->dropColumn('username');
        });

        Schema::rename('juriler', 'jurors');

        Schema::table('jurors', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        Schema::table('jurors', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jurors', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });

        Schema::table('jurors', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });

        Schema::rename('jurors', 'juriler');

        Schema::table('juriler', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
        });
    }
};
