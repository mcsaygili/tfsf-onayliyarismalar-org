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
        Schema::table('competition_types', function (Blueprint $table) {
            $table->string('icon_key', 80)->default('competition-standard')->after('code');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->string('public_slug', 180)->nullable()->unique()->after('id');
            $table->index(['status', 'published_at', 'audience'], 'competitions_public_listing_index');
            $table->index(['competition_type_id', 'application_ends_at'], 'competitions_public_type_date_index');
            $table->index('results_published_at', 'competitions_results_published_index');
        });

        $icons = [
            'standard' => 'competition-standard',
            'photographers-marathon' => 'competition-marathon',
            'cup' => 'competition-cup',
            'biennial-team-selection' => 'competition-biennial',
        ];

        foreach ($icons as $code => $iconKey) {
            DB::table('competition_types')->where('code', $code)->update(['icon_key' => $iconKey]);
        }

        $used = [];

        DB::table('competitions')->orderBy('id')->get(['id'])->each(function (object $competition) use (&$used): void {
            $name = DB::table('competition_translations')
                ->where('competition_id', $competition->id)
                ->orderByRaw("CASE WHEN locale = 'tr' THEN 0 ELSE 1 END")
                ->value('name');
            $base = Str::limit(Str::slug($name ?: 'tfsf-yarismasi'), 160, '');
            $base = $base !== '' ? $base : 'tfsf-yarismasi';
            $slug = $base;
            $suffix = 2;

            while (isset($used[$slug])) {
                $slug = $base.'-'.$suffix++;
            }

            $used[$slug] = true;
            DB::table('competitions')->where('id', $competition->id)->update(['public_slug' => $slug]);
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropIndex('competitions_public_listing_index');
            $table->dropIndex('competitions_public_type_date_index');
            $table->dropIndex('competitions_results_published_index');
            $table->dropUnique(['public_slug']);
            $table->dropColumn('public_slug');
        });

        Schema::table('competition_types', function (Blueprint $table) {
            $table->dropColumn('icon_key');
        });
    }
};
