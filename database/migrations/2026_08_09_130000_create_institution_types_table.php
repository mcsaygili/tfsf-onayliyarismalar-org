<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Kurum Türü. Öğrenim Durumu ile aynı desen: ana tablo +
 * ayrı çeviri tablosu (bkz. App\Models\Concerns\HasTranslations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('institution_type_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_type_id')->constrained('institution_types')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['institution_type_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_type_translations');
        Schema::dropIfExists('institution_types');
    }
};
