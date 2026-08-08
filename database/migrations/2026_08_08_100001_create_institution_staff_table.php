<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kurum personeli — guard: institution. Eski sistemde bu, aynı fiziksel
     * tabloyu paylaşan iki ayrı tip olarak vardı: "Kurum Görevlisi" (kuruma
     * bağlı, institution_id zorunlu) ve "Sekreterya" (kurumdan tamamen
     * bağımsız, yarışma bazında serbest atanan, institution_id her zaman
     * NULL). Derinlemesine incelemede bu ayrımın koddaki bir tutarsızlık
     * yüzünden "Kurum Görevlisi" tipinin kendi portalında hiçbir yarışma
     * göremediği ortaya çıktı — yarım kalmış/bozuk bir tasarım.
     *
     * Bilinçli karar: burada TEK bir model kullanılıyor, institution_id
     * HER ZAMAN zorunlu. "Bu yarışmadan sorumlu kişi" artık kurumdan
     * bağımsız bir havuzdan değil, doğrudan ilgili kurumun personelinden
     * seçilecek (competitions alanı tasarlanırken ele alınacak).
     */
    public function up(): void
    {
        Schema::create('institution_staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->boolean('status')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_staff');
    }
};
