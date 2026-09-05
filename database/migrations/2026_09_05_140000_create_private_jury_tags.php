<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jury_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('juror_id')->constrained('jurors')->cascadeOnDelete();
            $table->foreignUuid('competition_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->char('name_key', 64);
            $table->char('color', 7);
            $table->timestamps();
            $table->unique(['juror_id', 'competition_category_id', 'name_key'], 'jury_tags_owner_category_name_unique');
        });
        Schema::create('jury_tag_photos', function (Blueprint $table) {
            $table->foreignUuid('jury_tag_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('submission_photo_id')->constrained('competition_submission_photos')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['jury_tag_id', 'submission_photo_id'], 'jury_tag_photos_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jury_tag_photos');
        Schema::dropIfExists('jury_tags');
    }
};
