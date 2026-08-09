<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bir yarışma başvurusunun onay sürecindeki tam denetim izi —
     * append-only (satırlar hiç update edilmiyor, sadece ekleniyor).
     *
     * `actor_id`/`actor_type` (uuidMorphs) hem InstitutionStaff (kurum
     * tarafı düzenlemeleri) hem EysUser (onay/red/ek-bilgi işlemleri)
     * kaydedebiliyor — ikisi de UUID PK kullandığı için tek polimorfik
     * kolon yeterli.
     *
     * `changes` sadece `field_updated` aksiyonunda dolu oluyor:
     * {"subject": ["eski değer", "yeni değer"]} şeklinde, kurumun
     * needs_info durumundayken yaptığı düzenlemelerin eski/yeni farkını
     * tutuyor. `message` red/ek-bilgi-talebi metnini taşıyor.
     */
    public function up(): void
    {
        Schema::create('competition_status_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('message')->nullable();
            $table->json('changes')->nullable();
            $table->uuidMorphs('actor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_status_logs');
    }
};
