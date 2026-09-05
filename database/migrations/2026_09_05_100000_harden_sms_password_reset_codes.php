<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_password_reset_codes', function (Blueprint $table) {
            // Existing unbound challenges are rejected; users request a fresh code.
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('failed_attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sms_password_reset_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('failed_attempts');
        });
    }
};
