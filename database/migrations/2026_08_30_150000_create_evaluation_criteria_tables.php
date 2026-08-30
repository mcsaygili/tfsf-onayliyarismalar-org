<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('evaluation_criteria')) {
            Schema::create('evaluation_criteria', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 120)->unique();
                $table->unsignedSmallInteger('default_min_score')->default(0);
                $table->unsignedSmallInteger('default_max_score')->default(10);
                $table->decimal('default_weight', 7, 2)->default(1);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('evaluation_criterion_translations')) {
            Schema::create('evaluation_criterion_translations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('evaluation_criterion_id');
                $table->string('locale', 5);
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unique(['evaluation_criterion_id', 'locale'], 'evaluation_criterion_locale_unique');
                $table->foreign('evaluation_criterion_id', 'evaluation_criterion_translation_fk')
                    ->references('id')->on('evaluation_criteria')->cascadeOnDelete();
            });
        } else {
            Schema::table('evaluation_criterion_translations', function (Blueprint $table) {
                $table->unique(['evaluation_criterion_id', 'locale'], 'evaluation_criterion_locale_unique');
                $table->foreign('evaluation_criterion_id', 'evaluation_criterion_translation_fk')
                    ->references('id')->on('evaluation_criteria')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('competition_category_evaluation_criteria')) {
            Schema::create('competition_category_evaluation_criteria', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('competition_category_id');
                $table->foreignUuid('evaluation_criterion_id');
                $table->unsignedSmallInteger('min_score')->default(0);
                $table->unsignedSmallInteger('max_score')->default(10);
                $table->decimal('weight', 7, 2)->default(1);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['competition_category_id', 'evaluation_criterion_id'], 'category_evaluation_criterion_unique');
                $table->index(['competition_category_id', 'sort_order'], 'category_evaluation_criteria_order_index');
                $table->foreign('competition_category_id', 'category_evaluation_criterion_category_fk')->references('id')->on('competition_categories')->cascadeOnDelete();
                $table->foreign('evaluation_criterion_id', 'category_evaluation_criterion_reference_fk')->references('id')->on('evaluation_criteria')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_category_evaluation_criteria');
        Schema::dropIfExists('evaluation_criterion_translations');
        Schema::dropIfExists('evaluation_criteria');
    }
};
