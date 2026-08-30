<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regulation_items', function (Blueprint $table) {
            $table->string('render_scope', 30)->default('once')->after('content_type');
            $table->boolean('is_required')->default(true)->after('conditions');
        });
    }

    public function down(): void
    {
        Schema::table('regulation_items', function (Blueprint $table) {
            $table->dropColumn(['render_scope', 'is_required']);
        });
    }
};
