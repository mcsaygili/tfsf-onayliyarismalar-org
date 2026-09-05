<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_exception_grants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained()->restrictOnDelete();
            $table->uuidMorphs('actor');
            $table->boolean('active')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->text('reason');
            $table->foreignUuid('updated_by')->constrained('eys_users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['competition_id', 'actor_type', 'actor_id'], 'registration_exception_actor_unique');
        });
        Schema::table('competition_registrations', function (Blueprint $table): void {
            $table->boolean('documents_waived')->default(false);
            $table->string('approval_source', 20)->default('normal');
            $table->foreignUuid('exception_grant_id')->nullable()->constrained('registration_exception_grants', indexName: 'registration_exception_grant_fk')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competition_registrations', function (Blueprint $table): void {
            $table->dropForeign('registration_exception_grant_fk');
            $table->dropColumn(['documents_waived', 'approval_source', 'exception_grant_id']);
        });
        Schema::dropIfExists('registration_exception_grants');
    }
};
