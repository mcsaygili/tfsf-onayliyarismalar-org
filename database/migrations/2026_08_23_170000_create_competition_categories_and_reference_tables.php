<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'participant_genders' => ['participant_gender_id', 'participant_gender_translations'],
            'member_groups' => ['member_group_id', 'member_group_translations'],
            'capture_devices' => ['capture_device_id', 'capture_device_translations'],
        ] as $tableName => [$foreignKey, $translationTable]) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });

            Schema::create($translationTable, function (Blueprint $table) use ($tableName, $foreignKey, $translationTable) {
                $table->uuid('id')->primary();
                $table->foreignUuid($foreignKey);
                $table->string('locale', 5);
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->foreign($foreignKey, substr($translationTable.'_ref_fk', 0, 60))->references('id')->on($tableName)->cascadeOnDelete();
                $table->unique([$foreignKey, 'locale'], substr($translationTable.'_locale_uq', 0, 60));
            });
        }

        Schema::create('competition_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('birth_date_restricted')->default(false);
            $table->date('birth_date_from')->nullable();
            $table->date('birth_date_to')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('competition_category_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_category_id');
            $table->string('locale', 5);
            $table->string('name');
            $table->timestamps();
            $table->foreign('competition_category_id', 'cc_translation_category_fk')->references('id')->on('competition_categories')->cascadeOnDelete();
            $table->unique(['competition_category_id', 'locale'], 'cc_translation_locale_uq');
        });

        foreach ([
            'competition_category_gender' => ['participant_gender_id', 'participant_genders'],
            'competition_category_member_group' => ['member_group_id', 'member_groups'],
            'competition_category_capture_device' => ['capture_device_id', 'capture_devices'],
        ] as $tableName => [$foreignKey, $referenceTable]) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName, $foreignKey, $referenceTable) {
                $table->foreignUuid('competition_category_id');
                $table->foreignUuid($foreignKey);
                $prefix = match ($tableName) {
                    'competition_category_gender' => 'ccg',
                    'competition_category_member_group' => 'ccmg',
                    default => 'cccd',
                };
                $table->foreign('competition_category_id', $prefix.'_category_fk')->references('id')->on('competition_categories')->cascadeOnDelete();
                $table->foreign($foreignKey, $prefix.'_reference_fk')->references('id')->on($referenceTable)->cascadeOnDelete();
                $table->unique(['competition_category_id', $foreignKey], $prefix.'_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_category_capture_device');
        Schema::dropIfExists('competition_category_member_group');
        Schema::dropIfExists('competition_category_gender');
        Schema::dropIfExists('competition_category_translations');
        Schema::dropIfExists('competition_categories');
        Schema::dropIfExists('capture_device_translations');
        Schema::dropIfExists('capture_devices');
        Schema::dropIfExists('member_group_translations');
        Schema::dropIfExists('member_groups');
        Schema::dropIfExists('participant_gender_translations');
        Schema::dropIfExists('participant_genders');
    }
};
