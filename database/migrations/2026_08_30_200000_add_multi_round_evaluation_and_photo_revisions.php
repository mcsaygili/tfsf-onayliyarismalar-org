<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_evaluation_rounds', function (Blueprint $table) {
            $table->string('method', 20)->default('individual')->after('name');
            $table->boolean('is_final')->default(false)->after('method');
        });

        Schema::table('competition_submission_photos', function (Blueprint $table) {
            $table->timestamp('withdrawn_at')->nullable()->after('sort_order');
            $table->text('withdrawal_reason')->nullable()->after('withdrawn_at');
            $table->index(['competition_submission_id', 'withdrawn_at'], 'submission_photo_active_index');
        });

        Schema::create('competition_committee_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_evaluation_round_id')->constrained('competition_evaluation_rounds', indexName: 'committee_decision_round_fk')->cascadeOnDelete();
            $table->foreignUuid('submission_photo_id')->constrained('competition_submission_photos', indexName: 'committee_decision_photo_fk')->cascadeOnDelete();
            $table->string('decision', 20)->default('finalist');
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->text('note')->nullable();
            $table->foreignUuid('decided_by')->nullable()->constrained('eys_users', indexName: 'committee_decision_eys_fk')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_evaluation_round_id', 'submission_photo_id'], 'committee_round_photo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_committee_decisions');

        Schema::table('competition_submission_photos', function (Blueprint $table) {
            $table->dropIndex('submission_photo_active_index');
            $table->dropColumn(['withdrawn_at', 'withdrawal_reason']);
        });

        Schema::table('competition_evaluation_rounds', fn (Blueprint $table) => $table->dropColumn(['method', 'is_final']));
    }
};
