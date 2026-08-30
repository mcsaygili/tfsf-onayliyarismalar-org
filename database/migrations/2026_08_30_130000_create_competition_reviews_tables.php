<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('competitions')->where('status', 'pending_review')->update(['status' => 'submitted']);

        Schema::create('competition_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->unsignedSmallInteger('round');
            $table->foreignUuid('reviewer_id')->constrained('eys_users')->restrictOnDelete();
            $table->string('status')->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'round']);
            $table->index(['competition_id', 'status']);
        });

        Schema::create('competition_review_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_review_id')->constrained('competition_reviews')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_number');
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('addressed_at')->nullable();
            $table->foreignUuid('addressed_by')->nullable()->constrained('institution_staff')->nullOnDelete();
            $table->timestamps();

            $table->unique(['competition_review_id', 'step_number'], 'competition_review_step_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_review_steps');
        Schema::dropIfExists('competition_reviews');
        DB::table('competitions')
            ->whereIn('status', ['submitted', 'under_review', 'waiting_requirements'])
            ->update(['status' => 'pending_review']);
    }
};
