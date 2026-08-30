<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_references', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 120)->unique();
            $table->string('kind', 20)->default('award');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('award_reference_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('award_reference_id')->constrained('award_references')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['award_reference_id', 'locale'], 'award_reference_translation_unique');
        });

        Schema::create('competition_category_awards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->foreignUuid('award_reference_id')->constrained('award_references')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['competition_category_id', 'sort_order'], 'category_awards_order_index');
        });

        Schema::create('competition_category_award_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_category_award_id');
            $table->string('locale', 5);
            $table->string('special_award_text')->nullable();
            $table->string('material_award', 255)->nullable();
            $table->timestamps();
            $table->foreign('competition_category_award_id', 'cca_translation_award_fk')->references('id')->on('competition_category_awards')->cascadeOnDelete();
            $table->unique(['competition_category_award_id', 'locale'], 'category_award_translation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_category_award_translations');
        Schema::dropIfExists('competition_category_awards');
        Schema::dropIfExists('award_reference_translations');
        Schema::dropIfExists('award_references');
    }
};
