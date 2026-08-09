<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yarışma Sistemi — Şartname Maddesi. Her madde zorunlu olarak bir
 * Şartname Bölümü'ne bağlıdır (City→Country ile aynı desen). `code`
 * opsiyonel bir referans etiketi (ör. "a", "5.b") — kaynak şartnamedeki
 * fıkra harfiyle eşleştirme için, render'a etkisi yok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('regulation_section_id')->constrained('regulation_sections')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('code')->nullable();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('regulation_item_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('regulation_item_id')->constrained('regulation_items')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->text('content');
            $table->timestamps();
            $table->unique(['regulation_item_id', 'locale'], 'regulation_item_translations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulation_item_translations');
        Schema::dropIfExists('regulation_items');
    }
};
