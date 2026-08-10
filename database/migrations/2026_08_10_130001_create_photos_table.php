<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Üye (fotoğrafçı) kişisel fotoğraf portfolyosu — en fazla 30 fotoğraf/kullanıcı
 * (bkz. App\Models\Photo::MAX_PER_USER, proje planı "Üye Paneli — Fotoğraf
 * Portfolyosu"). Soft-delete YOK — kullanıcının kendi içeriğini silmesi
 * kalıcı; dosya temizliği PortfolioController::destroy()'da açıkça yapılır
 * (bu tablo başka hiçbir yere referans vermiyor, cascade sadece satırı siler,
 * diskteki dosyayı silmez).
 *
 * `taken_at` kullanıcının girdiği çekim tarihi — EXIF'in kendi
 * `exif_captured_at`'ından bilinçli olarak ayrı (biri kullanıcı beyanı,
 * diğeri dosyadan okunan ham veri).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('photo_category_id')->nullable()->constrained('photo_categories')->nullOnDelete();

            // Kullanıcı alanları
            $table->string('title');
            $table->string('location')->nullable();
            $table->date('taken_at');
            $table->json('tags')->nullable();
            $table->string('tags_text')->nullable();
            $table->text('description')->nullable();

            // Dosya
            $table->string('disk_path');
            $table->string('thumb_path')->nullable();
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // EXIF
            $table->string('camera_make')->nullable();
            $table->string('camera_model')->nullable();
            $table->string('lens')->nullable();
            $table->string('focal_length', 50)->nullable();
            $table->string('aperture', 50)->nullable();
            $table->string('shutter_speed', 50)->nullable();
            $table->unsignedInteger('iso')->nullable();
            $table->dateTime('exif_captured_at')->nullable();
            $table->string('color_space', 50)->nullable();
            $table->json('exif_raw')->nullable();
            $table->boolean('exif_missing')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
