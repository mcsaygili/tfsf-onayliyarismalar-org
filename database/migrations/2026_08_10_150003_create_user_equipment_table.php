<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Üye'nin (fotoğrafçı) sahip olduğu ekipman envanteri — bkz. proje planı
 * "Ekipmanlarım". Photos tablosu gibi soft-delete YOK: kullanıcı kendi
 * kaydını kalıcı siler. equipment_model_id restrictOnDelete — bir Model
 * forceDelete() ile silinmeye çalışılırsa ve kullanımdaysa engellenir
 * (normal akışta EYS destroy() zaten soft-delete yapar, bu bir güvenlik ağı).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_equipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('equipment_model_id')->constrained('equipment_models')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_equipment');
    }
};
