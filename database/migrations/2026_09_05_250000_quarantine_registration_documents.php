<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_registration_documents', function (Blueprint $table): void {
            $table->string('scan_status', 16)->default('pending')->index();
            $table->uuid('scan_token')->nullable();
            $table->timestamp('scan_started_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->unsignedInteger('scan_attempts')->default(0);
            $table->char('scan_sha256', 64)->nullable();
            $table->string('scan_policy', 80)->nullable();
            $table->string('scan_reason', 80)->nullable();
            $table->string('scan_engine', 255)->nullable();
        });
    }

    public function down(): void
    {
        // Old code exposes every mapped PDF: rollback requires restoring reviewed data from backup.
        // Fail closed by removing untrusted document mappings before removing their quarantine flags.
        DB::table('competition_registration_documents')->where(fn ($q) => $q->where('scan_status', '!=', 'clean')->orWhereNull('scanned_at')->orWhereNull('scan_sha256')->orWhereColumn('scan_sha256', '!=', 'sha256')->orWhereNull('scan_policy')->orWhere('scan_policy', '!=', 'qpdf-clamav-pdf-v1'))->delete();
        Schema::table('competition_registration_documents', function (Blueprint $table): void {
            $table->dropIndex(['scan_status']);
            $table->dropColumn(['scan_status', 'scan_token', 'scan_started_at', 'scanned_at', 'scan_attempts', 'scan_sha256', 'scan_policy', 'scan_reason', 'scan_engine']);
        });
    }
};
