<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->foreignUuid('representative_id')->nullable()->after('institution_staff_id')->constrained('representatives', indexName: 'competition_representative_fk')->nullOnDelete();
            $table->dateTime('evaluation_starts_at')->nullable()->after('competition_ends_at');
            $table->dateTime('evaluation_ends_at')->nullable()->after('evaluation_starts_at');
            $table->dateTime('results_published_at')->nullable()->after('evaluation_ends_at');
        });

        Schema::table('competition_categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_photos_per_participant')->default(4)->after('sort_order');
        });

        Schema::create('competition_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('draft');
            $table->json('eligibility_snapshot')->nullable();
            $table->foreignUuid('regulation_snapshot_id')->nullable()->constrained('competition_regulation_snapshots', indexName: 'entry_regulation_snapshot_fk')->nullOnDelete();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'user_id'], 'competition_entry_user_unique');
            $table->index(['competition_id', 'status'], 'competition_entry_status_index');
        });

        Schema::create('competition_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_entry_id')->constrained('competition_entries', indexName: 'submission_entry_fk')->cascadeOnDelete();
            $table->foreignUuid('competition_category_id')->constrained('competition_categories', indexName: 'submission_category_fk')->cascadeOnDelete();
            $table->string('status', 30)->default('draft');
            $table->json('eligibility_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique(['competition_entry_id', 'competition_category_id'], 'entry_category_submission_unique');
            $table->index(['competition_category_id', 'status'], 'category_submission_status_index');
        });

        Schema::create('competition_submission_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_submission_id')->constrained('competition_submissions', indexName: 'submission_photo_submission_fk')->cascadeOnDelete();
            $table->foreignUuid('source_photo_id')->nullable()->constrained('photos', indexName: 'submission_photo_source_fk')->nullOnDelete();
            $table->foreignUuid('capture_device_id')->nullable()->constrained('capture_devices', indexName: 'submission_photo_device_fk')->nullOnDelete();
            $table->string('disk_path');
            $table->string('jury_path')->nullable();
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('sha256', 64);
            $table->json('metadata_snapshot')->nullable();
            $table->json('processing_method_ids')->nullable();
            $table->json('eligibility_snapshot')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(10);
            $table->timestamps();
            $table->unique(['competition_submission_id', 'sha256'], 'submission_photo_hash_unique');
        });

        Schema::create('competition_submission_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_submission_id')->constrained('competition_submissions', indexName: 'submission_approval_submission_fk')->cascadeOnDelete();
            $table->string('approval_type', 30);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->nullableUuidMorphs('reviewed_by', 'submission_approval_reviewer_index');
            $table->text('note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_submission_id', 'approval_type'], 'submission_approval_type_unique');
        });

        Schema::create('competition_entry_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_entry_id')->constrained('competition_entries', indexName: 'entry_event_entry_fk')->cascadeOnDelete();
            $table->string('event', 50);
            $table->nullableUuidMorphs('actor', 'entry_event_actor_index');
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['competition_entry_id', 'created_at'], 'entry_event_timeline_index');
        });

        Schema::create('competition_evaluation_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_number')->default(1);
            $table->string('name')->default('Genel Değerlendirme');
            $table->string('status', 20)->default('planned');
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'round_number'], 'competition_round_number_unique');
        });

        Schema::create('jury_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_evaluation_round_id')->constrained('competition_evaluation_rounds', indexName: 'jury_score_round_fk')->cascadeOnDelete();
            $table->foreignUuid('juror_assignment_id')->constrained('competition_category_juror_assignments', indexName: 'jury_score_assignment_fk')->cascadeOnDelete();
            $table->foreignUuid('submission_photo_id')->constrained('competition_submission_photos', indexName: 'jury_score_photo_fk')->cascadeOnDelete();
            $table->foreignUuid('criterion_assignment_id')->constrained('competition_category_evaluation_criteria', indexName: 'jury_score_criterion_fk')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_evaluation_round_id', 'juror_assignment_id', 'submission_photo_id', 'criterion_assignment_id'], 'jury_score_unique');
        });

        Schema::create('jury_evaluation_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_evaluation_round_id')->constrained('competition_evaluation_rounds', indexName: 'jury_final_round_fk')->cascadeOnDelete();
            $table->foreignUuid('juror_assignment_id')->constrained('competition_category_juror_assignments', indexName: 'jury_final_assignment_fk')->cascadeOnDelete();
            $table->timestamp('finalized_at');
            $table->timestamps();
            $table->unique(['competition_evaluation_round_id', 'juror_assignment_id'], 'jury_round_assignment_final_unique');
        });

        Schema::create('competition_photo_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_evaluation_round_id')->constrained('competition_evaluation_rounds', indexName: 'photo_result_round_fk')->cascadeOnDelete();
            $table->foreignUuid('submission_photo_id')->constrained('competition_submission_photos', indexName: 'photo_result_photo_fk')->cascadeOnDelete();
            $table->decimal('total_score', 10, 2)->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->unsignedInteger('score_count')->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->timestamps();
            $table->unique(['competition_evaluation_round_id', 'submission_photo_id'], 'round_photo_result_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_photo_results');
        Schema::dropIfExists('jury_evaluation_submissions');
        Schema::dropIfExists('jury_scores');
        Schema::dropIfExists('competition_evaluation_rounds');
        Schema::dropIfExists('competition_entry_events');
        Schema::dropIfExists('competition_submission_approvals');
        Schema::dropIfExists('competition_submission_photos');
        Schema::dropIfExists('competition_submissions');
        Schema::dropIfExists('competition_entries');

        Schema::table('competition_categories', fn (Blueprint $table) => $table->dropColumn('max_photos_per_participant'));
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropForeign('competition_representative_fk');
            $table->dropColumn(['representative_id', 'evaluation_starts_at', 'evaluation_ends_at', 'results_published_at']);
        });
    }
};
