<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurors', function (Blueprint $table) {
            $table->string('registration_source', 32)->default('legacy')->after('status');
        });

        Schema::create('jury_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id');
            $table->foreignUuid('institution_id');
            $table->foreignUuid('invited_by')->nullable();
            $table->foreignUuid('accepted_juror_id')->nullable();
            $table->string('email');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('locale', 5)->default('tr');
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'email'], 'jury_invitation_competition_email_unique');
            $table->foreign('competition_id', 'jury_invitation_competition_fk')->references('id')->on('competitions')->cascadeOnDelete();
            $table->foreign('institution_id', 'jury_invitation_institution_fk')->references('id')->on('institutions')->cascadeOnDelete();
            $table->foreign('invited_by', 'jury_invitation_inviter_fk')->references('id')->on('institution_staff')->nullOnDelete();
            $table->foreign('accepted_juror_id', 'jury_invitation_accepted_juror_fk')->references('id')->on('jurors')->restrictOnDelete();
        });

        Schema::create('competition_category_juror_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_category_id');
            $table->foreignUuid('juror_id')->nullable();
            $table->foreignUuid('jury_invitation_id')->nullable();
            $table->foreignUuid('assigned_by')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['competition_category_id', 'juror_id'], 'category_juror_unique');
            $table->unique(['competition_category_id', 'jury_invitation_id'], 'category_jury_invitation_unique');
            $table->index(['competition_category_id', 'sort_order'], 'category_jurors_order_index');
            $table->foreign('competition_category_id', 'category_juror_category_fk')->references('id')->on('competition_categories')->cascadeOnDelete();
            $table->foreign('juror_id', 'category_juror_juror_fk')->references('id')->on('jurors')->restrictOnDelete();
            $table->foreign('jury_invitation_id', 'category_juror_invitation_fk')->references('id')->on('jury_invitations')->restrictOnDelete();
            $table->foreign('assigned_by', 'category_juror_assigner_fk')->references('id')->on('institution_staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_category_juror_assignments');
        Schema::dropIfExists('jury_invitations');

        Schema::table('jurors', function (Blueprint $table) {
            $table->dropColumn('registration_source');
        });
    }
};
