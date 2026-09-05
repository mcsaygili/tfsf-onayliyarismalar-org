<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_result_assets', function (Blueprint $table): void {
            // Existing version-2 assets contain awarded public works only.
            $table->boolean('is_public')->default(true);
            $table->uuid('owner_user_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Version-2 code treats every mapped asset as public. Preserve privacy on rollback.
        DB::table('competition_result_assets')->where('is_public', false)->delete();
        Schema::table('competition_result_assets', function (Blueprint $table): void {
            $table->dropIndex(['owner_user_id']);
            $table->dropColumn(['is_public', 'owner_user_id']);
        });
    }
};
