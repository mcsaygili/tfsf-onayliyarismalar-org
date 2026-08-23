<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('age_eligibility_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->unsignedTinyInteger('minimum_age')->nullable();
            $table->unsignedTinyInteger('maximum_age')->nullable();
            $table->boolean('minimum_inclusive')->default(true);
            $table->boolean('maximum_inclusive')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('age_eligibility_rule_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('age_eligibility_rule_id');
            $table->string('locale', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('age_eligibility_rule_id', 'age_rule_translation_rule_fk')->references('id')->on('age_eligibility_rules')->cascadeOnDelete();
            $table->unique(['age_eligibility_rule_id', 'locale'], 'age_rule_translation_locale_uq');
        });

        Schema::table('competition_categories', function (Blueprint $table) {
            $table->foreignUuid('age_eligibility_rule_id')->nullable()->after('sort_order')->constrained('age_eligibility_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('age_eligibility_rule_id');
        });
        Schema::dropIfExists('age_eligibility_rule_translations');
        Schema::dropIfExists('age_eligibility_rules');
    }
};
