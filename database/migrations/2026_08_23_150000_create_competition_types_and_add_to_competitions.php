<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('competition_type_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_type_id')->constrained('competition_types')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['competition_type_id', 'locale']);
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->foreignUuid('competition_type_id')
                ->nullable()
                ->after('infrastructure_provider')
                ->constrained('competition_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_type_id');
        });

        Schema::dropIfExists('competition_type_translations');
        Schema::dropIfExists('competition_types');
    }
};
