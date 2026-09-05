<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', fn (Blueprint $table) => $table->unsignedInteger('results_edit_version')->default(0));
        Schema::table('competition_evaluation_rounds', function (Blueprint $table) {
            $table->char('results_state_hash', 64)->nullable();
            $table->char('awards_context_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('competition_evaluation_rounds', fn (Blueprint $table) => $table->dropColumn(['results_state_hash', 'awards_context_hash']));
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn('results_edit_version'));
    }
};
