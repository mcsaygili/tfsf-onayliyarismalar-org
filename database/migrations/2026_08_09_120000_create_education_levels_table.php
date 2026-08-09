<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Öğrenim Durumu. Ülke/Şehir ile aynı desen: ana tablo +
 * ayrı çeviri tablosu (bkz. App\Models\Concerns\HasTranslations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('education_level_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('education_level_id')->constrained('education_levels')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['education_level_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_level_translations');
        Schema::dropIfExists('education_levels');
    }
};
