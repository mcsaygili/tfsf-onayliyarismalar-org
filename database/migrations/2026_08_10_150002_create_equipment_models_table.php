<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Ekipman Modeli (ör. "EOS R5", "RF 50mm F1.2L USM").
 * Bir Marka'ya ve bir Ekipman Türü'ne bağlıdır (City'nin Country'ye bağlı
 * olması gibi hiyerarşik çocuk satır). Model adı da evrensel — çeviri yok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('equipment_brand_id')->constrained('equipment_brands')->cascadeOnDelete();
            $table->foreignUuid('equipment_type_id')->constrained('equipment_types')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['equipment_brand_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_models');
    }
};
