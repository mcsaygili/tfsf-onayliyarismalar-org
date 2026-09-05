<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_submission_photos', function (Blueprint $table) {
            $table->timestamp('jury_sanitized_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('competition_submission_photos', function (Blueprint $table) {
            $table->dropIndex(['jury_sanitized_at']);
            $table->dropColumn('jury_sanitized_at');
        });
    }
};
