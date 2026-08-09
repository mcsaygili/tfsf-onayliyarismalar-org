<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Ülke/Şehir. Şema ve veri (bkz.
 * database/seeders/ReferenceDataSeeder.php + database/seeders/data/*.sql)
 * tfsf-org-tr projesinden birebir aktarıldı — legacy UUID'ler korunuyor,
 * bu yüzden iki proje arasında aynı kayıt aynı id'yi taşıyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('iso_alpha2', 2)->nullable()->index();
            $table->string('iso_alpha3', 3)->nullable();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('country_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('official_name');
            $table->string('short_name')->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();
            $table->unique(['country_id', 'locale']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('city_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('official_name');
            $table->timestamps();
            $table->unique(['city_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_translations');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('countries');
    }
};
