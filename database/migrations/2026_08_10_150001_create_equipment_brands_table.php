<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referans veriler — Marka (Canon/Nikon/Sigma/vb.). Kod tabanındaki ilk
 * çeviri gerektirmeyen referans tablosu — marka adları evrensel, dilden
 * bağımsız. Tekillik DB-unique yerine EquipmentBrandController::validateData()
 * içinde soft-delete'e duyarlı bir Rule::unique ile sağlanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_brands');
    }
};
