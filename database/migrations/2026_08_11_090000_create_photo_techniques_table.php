<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Fotoğraf Çekim Tekniği (Yapay Zeka/HDR/Focus Stacking/vb.
 * beyanları). Fotoğraf Kategorisi ile aynı desen: ana tablo + ayrı çeviri
 * tablosu (bkz. App\Models\Concerns\HasTranslations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_techniques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('photo_technique_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photo_technique_id')->constrained('photo_techniques')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['photo_technique_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_technique_translations');
        Schema::dropIfExists('photo_techniques');
    }
};
