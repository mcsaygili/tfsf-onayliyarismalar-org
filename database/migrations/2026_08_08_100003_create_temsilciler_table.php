<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TFSF bölge temsilcisi hesapları — guard: temsilci. Eski temsilci +
     * temsilci_detay. city/region/dernek_bilgi FK'leri referans veri
     * modülü sonraki fazda gelene kadar bilinçli olarak boş bırakıldı.
     */
    public function up(): void
    {
        Schema::create('temsilciler', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->uuid('city_id')->nullable();
            $table->uuid('region_id')->nullable();
            $table->uuid('dernek_bilgi_id')->nullable();
            $table->boolean('status')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temsilciler');
    }
};
