<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotoğraf↔Çekim Tekniği etiketi — photo_equipment ile aynı M:N pivot deseni:
 * surrogate id yok, composite PK (HasUuids + attach()/sync() bir surrogate
 * UUID PK'yi otomatik dolduramaz).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_photo_techniques', function (Blueprint $table) {
            $table->foreignUuid('photo_id')->constrained('photos')->cascadeOnDelete();
            $table->foreignUuid('photo_technique_id')->constrained('photo_techniques')->cascadeOnDelete();
            $table->primary(['photo_id', 'photo_technique_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_photo_techniques');
    }
};
