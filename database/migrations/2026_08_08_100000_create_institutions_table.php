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
     *
     * `name` nullable: kurum kaydı, self-servis kayıt akışında sadece
     * e-posta+şifre ile oluşturuluyor — kurum adı ve diğer bilgiler giriş
     * sonrası ayrı bir ekrandan doldurulacak (bkz. RegisteredController).
     *
     * `approved_at`: eski sistemde yoktu — bu fazda TÜM kayıtlar otomatik
     * onaylı sayılıyor (kayıt anında now() atanıyor). İleride TFSF admin
     * onayı gerektiren gerçek bir iş akışına dönüşecek; o zamana kadar
     * null bırakılan bir kurum olmayacak ama alan hazır bekliyor.
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
