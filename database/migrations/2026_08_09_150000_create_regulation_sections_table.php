<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yarışma Sistemi — Şartname Bölümü. Öğrenim Durumu/Kurum Türü ile aynı
 * desen: ana tablo + ayrı çeviri tablosu (bkz. App\Models\Concerns\HasTranslations).
 * Bölümler, dinamik şartname üretiminde kullanılacak başlık kütüphanesi
 * (ör. "Yarışma Koşulları", "Telif Hakkı") — asıl tüketim/render motoru
 * ayrı bir faz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulation_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('regulation_section_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('regulation_section_id')->constrained('regulation_sections')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['regulation_section_id', 'locale'], 'regulation_section_translations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulation_section_translations');
        Schema::dropIfExists('regulation_sections');
    }
};
