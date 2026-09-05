<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_submission_photos', fn (Blueprint $table) => $table->char('anonymous_code', 16)->nullable()->unique());
        DB::table('competition_submission_photos')->select('id')->orderBy('id')->chunkById(500, function ($photos): void {
            foreach ($photos as $photo) {
                do {
                    $code = strtoupper(bin2hex(random_bytes(8)));
                } while (DB::table('competition_submission_photos')->where('anonymous_code', $code)->exists());
                DB::table('competition_submission_photos')->where('id', $photo->id)->update(['anonymous_code' => $code]);
            }
        });
        Schema::table('competition_submission_photos', fn (Blueprint $table) => $table->char('anonymous_code', 16)->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('competition_submission_photos', function (Blueprint $table): void {
            $table->dropUnique(['anonymous_code']);
            $table->dropColumn('anonymous_code');
        });
    }
};
