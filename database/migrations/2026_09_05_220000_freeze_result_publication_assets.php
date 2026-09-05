<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_result_publications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('snapshot_version')->default(1);
            $table->text('search_text')->nullable();
        });
        DB::table('competition_result_publications')->select('id', 'snapshot')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $snapshot = json_decode($row->snapshot, true, flags: JSON_THROW_ON_ERROR);
                $names = data_get($snapshot, 'competition.name', []);
                DB::table('competition_result_publications')->where('id', $row->id)->update(['search_text' => implode(' ', is_array($names) ? $names : [$names]).' '.data_get($snapshot, 'competition.institution', '')]);
            }
        });
        Schema::table('competition_result_publications', fn (Blueprint $table) => $table->dropForeign(['competition_id']));
        Schema::table('competition_result_publications', fn (Blueprint $table) => $table->foreign('competition_id')->references('id')->on('competitions')->restrictOnDelete());
        Schema::create('competition_result_assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('publication_id')->constrained('competition_result_publications')->cascadeOnDelete();
            $table->uuid('source_photo_id')->index();
            $table->string('disk_path')->unique();
            $table->char('sha256', 64);
            $table->string('mime_type', 80);
            $table->unsignedBigInteger('file_size_bytes');
            $table->unique(['publication_id', 'source_photo_id'], 'publication_photo_asset_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_result_assets');
        Schema::table('competition_result_publications', fn (Blueprint $table) => $table->dropForeign(['competition_id']));
        Schema::table('competition_result_publications', fn (Blueprint $table) => $table->foreign('competition_id')->references('id')->on('competitions')->cascadeOnDelete());
        Schema::table('competition_result_publications', fn (Blueprint $table) => $table->dropColumn(['snapshot_version', 'search_text']));
    }
};
