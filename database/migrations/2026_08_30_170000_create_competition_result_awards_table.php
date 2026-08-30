<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_result_awards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_photo_result_id')->constrained('competition_photo_results', indexName: 'result_award_result_fk')->cascadeOnDelete();
            $table->foreignUuid('competition_category_award_id')->constrained('competition_category_awards', indexName: 'result_award_category_award_fk')->cascadeOnDelete();
            $table->unsignedSmallInteger('slot_number')->default(1);
            $table->foreignUuid('assigned_by')->nullable()->constrained('eys_users', indexName: 'result_award_assigner_fk')->nullOnDelete();
            $table->timestamps();

            $table->unique(['competition_category_award_id', 'slot_number'], 'category_award_slot_unique');
            $table->unique(['competition_photo_result_id', 'competition_category_award_id'], 'result_category_award_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_result_awards');
    }
};
