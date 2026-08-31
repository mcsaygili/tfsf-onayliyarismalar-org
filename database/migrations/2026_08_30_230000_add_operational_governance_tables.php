<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24)->default('competition');
            $table->text('reason');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('eys_users', indexName: 'member_restriction_creator_fk')->nullOnDelete();
            $table->timestamp('lifted_at')->nullable();
            $table->foreignUuid('lifted_by')->nullable()->constrained('eys_users', indexName: 'member_restriction_lifter_fk')->nullOnDelete();
            $table->text('lift_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'starts_at', 'ends_at'], 'member_restriction_active_index');
        });

        Schema::create('competition_monitoring_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('representative_id')->constrained('representatives', indexName: 'monitoring_report_representative_fk')->cascadeOnDelete();
            $table->string('status', 24)->default('observation');
            $table->string('subject', 255);
            $table->text('note');
            $table->timestamp('observed_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['competition_id', 'observed_at'], 'monitoring_report_timeline_index');
        });

        Schema::create('competition_jury_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_evaluation_round_id')->unique('jury_session_round_unique')->constrained('competition_evaluation_rounds', indexName: 'jury_session_round_fk')->cascadeOnDelete();
            $table->string('status', 20)->default('planned');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('location')->nullable();
            $table->unsignedTinyInteger('quorum')->default(1);
            $table->text('minutes')->nullable();
            $table->foreignUuid('opened_by')->nullable()->constrained('eys_users', indexName: 'jury_session_opener_fk')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->foreignUuid('closed_by')->nullable()->constrained('eys_users', indexName: 'jury_session_closer_fk')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('competition_jury_session_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_jury_session_id')->constrained('competition_jury_sessions', indexName: 'jury_attendance_session_fk')->cascadeOnDelete();
            $table->foreignUuid('juror_id')->constrained('jurors', indexName: 'jury_attendance_juror_fk')->cascadeOnDelete();
            $table->string('attendance_status', 20)->default('invited');
            $table->boolean('conflict_declared')->default(false);
            $table->text('conflict_note')->nullable();
            $table->timestamp('declared_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_jury_session_id', 'juror_id'], 'jury_session_juror_unique');
        });

        Schema::create('competition_result_publications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->text('publication_note')->nullable();
            $table->text('correction_note')->nullable();
            $table->foreignUuid('published_by')->nullable()->constrained('eys_users', indexName: 'result_publication_user_fk')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'version'], 'competition_result_publication_version_unique');
        });

        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('notification_id')->nullable()->index();
            $table->string('notification_type');
            $table->nullableUuidMorphs('notifiable', 'notification_delivery_notifiable_index');
            $table->string('channel', 30);
            $table->string('status', 20);
            $table->text('response')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
        Schema::dropIfExists('competition_result_publications');
        Schema::dropIfExists('competition_jury_session_attendances');
        Schema::dropIfExists('competition_jury_sessions');
        Schema::dropIfExists('competition_monitoring_reports');
        Schema::dropIfExists('member_restrictions');
    }
};
