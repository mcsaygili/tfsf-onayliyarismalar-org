<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('publication_state', 24)->default('unpublished')->after('published_at');
            $table->timestamp('publication_state_changed_at')->nullable()->after('publication_state');
            $table->unsignedInteger('results_publication_version')->default(0)->after('results_published_at');
            $table->index(['publication_state', 'published_at'], 'competition_publication_state_index');
        });

        DB::table('competitions')
            ->where('status', 'approved')
            ->whereNotNull('published_at')
            ->update([
                'publication_state' => 'published',
                'publication_state_changed_at' => DB::raw('published_at'),
            ]);

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropIndex('competition_publication_state_index');
            $table->dropColumn(['publication_state', 'publication_state_changed_at', 'results_publication_version']);
        });
    }
};
