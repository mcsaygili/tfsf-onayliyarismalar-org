<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Ekipman Türü (Kamera Gövdesi/Objektif/Flaş/vb.). Fotoğraf
 * Kategorisi ile aynı desen: ana tablo + ayrı çeviri tablosu (bkz.
 * App\Models\Concerns\HasTranslations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('equipment_type_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('equipment_type_id')->constrained('equipment_types')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['equipment_type_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_type_translations');
        Schema::dropIfExists('equipment_types');
    }
};
