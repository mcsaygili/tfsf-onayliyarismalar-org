<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_categories', fn (Blueprint $table) => $table->boolean('photos_grouped')->default(false));
        Schema::table('competition_submissions', fn (Blueprint $table) => $table->char('series_code', 16)->nullable()->unique());
        DB::table('competition_submissions')->select('id')->orderBy('id')->chunkById(500, function ($photos): void {
            foreach ($photos as $photo) {
                do {
                    $code = strtoupper(bin2hex(random_bytes(8)));
                } while (DB::table('competition_submissions')->where('series_code', $code)->exists());
                DB::table('competition_submissions')->where('id', $photo->id)->update(['series_code' => $code]);
            }
        });
        Schema::table('competition_submissions', fn (Blueprint $table) => $table->char('series_code', 16)->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('competition_categories', fn (Blueprint $table) => $table->dropColumn('photos_grouped'));
        Schema::table('competition_submissions', function (Blueprint $table): void {
            $table->dropUnique(['series_code']);
            $table->dropColumn('series_code');
        });
    }
};
