<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('competitions', 'application_starts_at')) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->dateTime('application_starts_at')->nullable()->after('partners');
                $table->dateTime('application_ends_at')->nullable()->after('application_starts_at');
                $table->dateTime('competition_ends_at')->nullable()->after('application_ends_at');
                $table->string('external_provider_name')->nullable()->after('infrastructure_provider');
                $table->string('external_entry_url', 2048)->nullable()->after('external_provider_name');
                $table->timestamp('external_responsibility_accepted_at')->nullable()->after('external_entry_url');
            });
        }

        if (! Schema::hasColumn('competition_types', 'requires_location')) {
            Schema::table('competition_types', function (Blueprint $table) {
                $table->boolean('requires_location')->default(false)->after('code');
                $table->boolean('requires_approval_process')->default(false)->after('requires_location');
                $table->boolean('is_system')->default(false)->after('status');
                $table->unsignedInteger('version')->default(1)->after('is_system');
            });
        }

        foreach (['participant_approval_processes', 'participant_genders', 'age_eligibility_rules', 'member_groups', 'capture_devices'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'is_system')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->boolean('is_system')->default(false)->after('status');
                    $table->unsignedInteger('version')->default(1)->after('is_system');
                });
            }
        }

        if (! Schema::hasColumn('competition_categories', 'member_group_match_mode')) {
            Schema::table('competition_categories', function (Blueprint $table) {
                $table->string('member_group_match_mode', 10)->default('any')->after('age_eligibility_rule_id');
            });
        }

        if (! Schema::hasTable('competition_capture_regions')) {
            Schema::create('competition_capture_regions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('country_id')->constrained('countries')->restrictOnDelete();
                $table->foreignUuid('city_id')->constrained('cities')->restrictOnDelete();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['competition_id', 'country_id', 'city_id'], 'competition_capture_region_unique');
            });
        }

        if (! Schema::hasTable('processing_methods')) {
            Schema::create('processing_methods', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('processing_method_translations')) {
            Schema::create('processing_method_translations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('processing_method_id')->constrained('processing_methods')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unique(['processing_method_id', 'locale'], 'processing_method_translation_unique');
            });
        }

        if (! Schema::hasTable('competition_category_processing_method')) {
            Schema::create('competition_category_processing_method', function (Blueprint $table) {
                $table->uuid('competition_category_id');
                $table->uuid('processing_method_id');
                $table->foreign('competition_category_id', 'ccpm_category_fk')->references('id')->on('competition_categories')->cascadeOnDelete();
                $table->foreign('processing_method_id', 'ccpm_method_fk')->references('id')->on('processing_methods')->cascadeOnDelete();
                $table->unique(['competition_category_id', 'processing_method_id'], 'cc_processing_method_unique');
            });
        } else {
            Schema::table('competition_category_processing_method', function (Blueprint $table) {
                $table->foreign('competition_category_id', 'ccpm_category_fk')->references('id')->on('competition_categories')->cascadeOnDelete();
                $table->foreign('processing_method_id', 'ccpm_method_fk')->references('id')->on('processing_methods')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('regulation_sections', 'code')) {
            Schema::table('regulation_sections', function (Blueprint $table) {
                $table->string('code')->nullable()->after('id');
                $table->boolean('is_system')->default(false)->after('status');
                $table->unsignedInteger('version')->default(1)->after('is_system');
                $table->unique('code');
            });
        }

        if (! Schema::hasColumn('regulation_items', 'content_type')) {
            Schema::table('regulation_items', function (Blueprint $table) {
                $table->string('content_type', 30)->default('fixed')->after('code');
                $table->string('source_key')->nullable()->after('content_type');
                $table->json('conditions')->nullable()->after('source_key');
                $table->boolean('is_system')->default(false)->after('status');
                $table->unsignedInteger('version')->default(1)->after('is_system');
            });
        }

        if (! Schema::hasTable('competition_regulation_inputs')) {
            Schema::create('competition_regulation_inputs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('regulation_item_id')->constrained('regulation_items')->restrictOnDelete();
                $table->string('locale', 5);
                $table->text('content')->nullable();
                $table->timestamps();
                $table->unique(['competition_id', 'regulation_item_id', 'locale'], 'competition_regulation_input_unique');
            });
        }

        if (! Schema::hasTable('competition_regulation_snapshots')) {
            Schema::create('competition_regulation_snapshots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('competition_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->json('content');
                $table->timestamp('compiled_at');
                $table->timestamps();
                $table->unique(['competition_id', 'version'], 'competition_regulation_snapshot_unique');
            });
        }

        $this->backfillCaptureRegions();
    }

    private function backfillCaptureRegions(): void
    {
        $now = now();

        DB::table('competitions')
            ->whereNotNull('country_id')
            ->whereNotNull('city_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $competition) use ($now) {
                DB::table('competition_capture_regions')->insertOrIgnore([
                    'id' => (string) Str::uuid(),
                    'competition_id' => $competition->id,
                    'country_id' => $competition->country_id,
                    'city_id' => $competition->city_id,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_regulation_snapshots');
        Schema::dropIfExists('competition_regulation_inputs');

        Schema::table('regulation_items', function (Blueprint $table) {
            $table->dropColumn(['content_type', 'source_key', 'conditions', 'is_system', 'version']);
        });
        Schema::table('regulation_sections', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'is_system', 'version']);
        });

        Schema::dropIfExists('competition_category_processing_method');
        Schema::dropIfExists('processing_method_translations');
        Schema::dropIfExists('processing_methods');
        Schema::dropIfExists('competition_capture_regions');

        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropColumn('member_group_match_mode');
        });

        foreach (['participant_approval_processes', 'participant_genders', 'age_eligibility_rules', 'member_groups', 'capture_devices'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['is_system', 'version']);
            });
        }

        Schema::table('competition_types', function (Blueprint $table) {
            $table->dropColumn(['requires_location', 'requires_approval_process', 'is_system', 'version']);
        });
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
                'application_starts_at', 'application_ends_at', 'competition_ends_at',
                'external_provider_name', 'external_entry_url', 'external_responsibility_accepted_at',
            ]);
        });
    }
};
