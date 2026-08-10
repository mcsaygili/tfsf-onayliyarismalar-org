<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Fotoğraf Kategorisi (bkz. Üye Portfolyosu). Öğrenim
 * Durumu/Kurum Türü ile aynı desen: ana tablo + ayrı çeviri tablosu (bkz.
 * App\Models\Concerns\HasTranslations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('photo_category_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photo_category_id')->constrained('photo_categories')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['photo_category_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_category_translations');
        Schema::dropIfExists('photo_categories');
    }
};
