<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_staff', function (Blueprint $table): void {
            $table->uuid('institution_id')->nullable()->change();
            $table->string('account_kind', 20)->default('institution');
        });
        // Preserve unbound secretariats when reapplying after a rollback.
        DB::table('institution_staff')->whereNull('institution_id')->update(['account_kind' => 'secretariat']);
        Schema::table('competitions', function (Blueprint $table): void {
            $table->foreignUuid('secretariat_id')->nullable()->constrained('institution_staff', indexName: 'competition_secretariat_fk')->restrictOnDelete();
            $table->unsignedInteger('secretariat_version')->default(0);
        });
        Schema::create('secretariat_account_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('institution_staff')->restrictOnDelete();
            $table->uuidMorphs('actor');
            $table->string('action', 30);
            $table->json('changes');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secretariat_account_events');
        Schema::table('competitions', function (Blueprint $table): void {
            $table->dropForeign('competition_secretariat_fk');
            $table->dropColumn(['secretariat_id', 'secretariat_version']);
        });
        Schema::table('institution_staff', fn (Blueprint $table) => $table->dropColumn('account_kind'));
        // Keep institution_id nullable: rollback must not delete or invent owners for accounts.
        // Earlier application code denies these unbound accounts access.
    }
};
