<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bakım Modu — Kurum/Temsilci/Jüri/Üye subdomainleri için ayrı ayrı
 * açılıp/kapatılabilen bakım modu satırları (EYS bilinçli olarak hariç —
 * admin bakım modunu kapatabilmek için her zaman erişebilmeli).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_modes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('module')->unique();
            $table->boolean('enabled')->default(false);
            $table->text('message')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('eys_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_modes');
    }
};
