<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table): void {
            $table->boolean('registration_required')->default(false);
            $table->unsignedTinyInteger('registration_document_min')->default(0);
            $table->string('registration_reviewer', 20)->default('institution');
            $table->unsignedInteger('registration_sequence')->default(0);
        });
        Schema::create('competition_registrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('status', 24)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('reviewer', 20);
            $table->unsignedTinyInteger('document_min');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->nullableUuidMorphs('reviewed_by');
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'user_id'], 'registration_member_unique');
            $table->unique(['competition_id', 'number'], 'registration_number_unique');
        });
        Schema::create('competition_registration_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('competition_registrations')->restrictOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->unsignedInteger('version');
            $table->boolean('is_current')->default(true);
            $table->string('disk_path')->unique();
            $table->char('sha256', 64);
            $table->unsignedBigInteger('file_size_bytes');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['registration_id', 'slot', 'version'], 'registration_document_version_unique');
        });
        Schema::create('competition_registration_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('competition_registrations')->restrictOnDelete();
            $table->string('event');
            $table->unsignedInteger('version');
            $table->uuidMorphs('actor');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_registration_events');
        Schema::dropIfExists('competition_registration_documents');
        Schema::dropIfExists('competition_registrations');
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn(['registration_required', 'registration_document_min', 'registration_reviewer', 'registration_sequence']));
    }
};
