<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_approval_processes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('participant_approval_process_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('participant_approval_process_id')
                ->constrained('participant_approval_processes', indexName: 'participant_approval_translation_process_fk')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['participant_approval_process_id', 'locale'], 'participant_approval_process_locale_unique');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->foreignUuid('country_id')->nullable()->after('competition_type_id')->constrained('countries')->nullOnDelete();
            $table->foreignUuid('city_id')->nullable()->after('country_id')->constrained('cities')->nullOnDelete();
            $table->foreignUuid('participant_approval_process_id')
                ->nullable()
                ->after('city_id')
                ->constrained('participant_approval_processes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('participant_approval_process_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::dropIfExists('participant_approval_process_translations');
        Schema::dropIfExists('participant_approval_processes');
    }
};
