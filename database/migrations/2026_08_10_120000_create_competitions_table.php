<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bir kurumun yarışma başvurusu — taslaktan yayına kadar tek kayıt
     * (bkz. proje planı "Kurum Paneli — Yarışma Ekleme Sihirbazı"). Sihirbaz
     * 10 adımlı tasarlandı. Yarışma bilgileri (name/partners/subject/purpose)
     * 2. adımda yönetilir. Düzenleyen kurum ayrıca
     * kopyalanmaz; institution_id ilişkisi üzerinden salt okunur gösterilir.
     * Kalan adımlar
     * app/Support/CompetitionWizard/PlaceholderStep üzerinden ilerliyor,
     * ileride yeni sütunlar eklenerek genişleyecek.
     *
     * `status`/`current_step`/`reviewed_*`/`published_at` bilinçli olarak
     * modelin #[Fillable] listesi DIŞINDA tutuluyor — bunlar sadece
     * controller mantığıyla (onaya gönder / EYS incelemesi) değişmeli,
     * kullanıcı formundan asla doğrudan yazılmamalı.
     *
     * `status` değerleri App\Enums\CompetitionStatus'te toplu: draft
     * (taslak) → pending_review (onay bekliyor) → approved/rejected/
     * needs_info. `rejected` bu kayıt için kesin — kurum düzeltip
     * yeniden gönderemez, yeni taslak başlatır. `needs_info` ise
     * düzenle-ve-yeniden-gönder döngüsü (bkz. competition_status_logs).
     */
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->foreignUuid('institution_staff_id')->nullable()->constrained('institution_staff')->nullOnDelete();

            // Adım 2 — Yarışma Bilgileri
            $table->string('name')->nullable();
            $table->text('partners')->nullable();
            $table->text('subject')->nullable();
            $table->text('purpose')->nullable();

            // Sihirbaz / onay süreci takibi
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('eys_users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->text('latest_review_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
