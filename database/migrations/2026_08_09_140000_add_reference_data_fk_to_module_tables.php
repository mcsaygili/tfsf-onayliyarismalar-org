<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EYS'in Kurum/Temsilci/Jüri/Üye CRUD ekranları referans verilere
 * bağlanıyor: Kurum -> Kurum Türü, Temsilci/Jüri/Üye -> Öğrenim Durumu.
 * Her ikisi de nullable — mevcut kayıtlar etkilenmez, form üzerinden
 * isteğe bağlı doldurulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->foreignUuid('institution_type_id')->nullable()->after('name')
                ->constrained('institution_types')->nullOnDelete();
        });

        Schema::table('representatives', function (Blueprint $table) {
            $table->foreignUuid('education_level_id')->nullable()->after('last_name')
                ->constrained('education_levels')->nullOnDelete();
        });

        Schema::table('jurors', function (Blueprint $table) {
            $table->foreignUuid('education_level_id')->nullable()->after('last_name')
                ->constrained('education_levels')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('education_level_id')->nullable()->after('last_name')
                ->constrained('education_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_type_id');
        });

        Schema::table('representatives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('education_level_id');
        });

        Schema::table('jurors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('education_level_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('education_level_id');
        });
    }
};
