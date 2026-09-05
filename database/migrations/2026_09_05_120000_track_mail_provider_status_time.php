<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_send_logs', function (Blueprint $table) {
            $table->timestamp('provider_status_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mail_send_logs', function (Blueprint $table) {
            $table->dropColumn('provider_status_at');
        });
    }
};
