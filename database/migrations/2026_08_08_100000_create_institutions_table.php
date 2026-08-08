<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Yarışma düzenleyen kurum/dernek/üniversite gibi organizasyonlar
     * (eski sistemdeki "Kurum" — bkz. docs/MEVCUT-YAPI-ANALIZ.md).
     * Eski şema sadece isim+durumdan ibaretti; burada gerçekçi bir
     * kurumsal profil için email/phone/website/address bilinçli olarak
     * eklendi (eski sistemin birebir taşınmadığı bir nokta).
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
