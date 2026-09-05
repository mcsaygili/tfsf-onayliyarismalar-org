<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->boolean('photo_story_required')->default(false);
            $table->boolean('category_story_required')->default(false);
            $table->boolean('photo_order_required')->default(false);
        });
        Schema::table('competition_submissions', function (Blueprint $table) {
            $table->text('category_story')->nullable();
            $table->unsignedInteger('details_version')->default(0);
        });
        Schema::table('competition_submission_photos', fn (Blueprint $table) => $table->json('declaration')->nullable());
    }

    public function down(): void
    {
        Schema::table('competition_submission_photos', fn (Blueprint $table) => $table->dropColumn('declaration'));
        Schema::table('competition_submissions', fn (Blueprint $table) => $table->dropColumn(['category_story', 'details_version']));
        Schema::table('competition_categories', fn (Blueprint $table) => $table->dropColumn(['photo_story_required', 'category_story_required', 'photo_order_required']));
    }
};
