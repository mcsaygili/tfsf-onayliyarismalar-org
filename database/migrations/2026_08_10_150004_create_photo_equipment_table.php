<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotoğraf↔Ekipman etiketi — kod tabanındaki ilk gerçek M:N pivot. Spatie'nin
 * role_has_permissions tablosundaki desene uyarak surrogate id yok, composite
 * PK kullanılıyor (HasUuids + attach()/sync() bir surrogate UUID PK'yi
 * otomatik dolduramaz — composite PK ek kod gerektirmeden çalışır).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_equipment', function (Blueprint $table) {
            $table->foreignUuid('photo_id')->constrained('photos')->cascadeOnDelete();
            $table->foreignUuid('user_equipment_id')->constrained('user_equipment')->cascadeOnDelete();
            $table->primary(['photo_id', 'user_equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_equipment');
    }
};
