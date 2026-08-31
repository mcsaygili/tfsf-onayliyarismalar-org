<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_status_logs', function (Blueprint $table) {
            $table->string('actor_guard', 32)->nullable()->after('actor_type');
            $table->string('request_id', 100)->nullable()->index()->after('actor_guard');
            $table->string('ip_address', 45)->nullable()->after('request_id');
            $table->string('user_agent', 500)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('competition_status_logs', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropColumn(['actor_guard', 'request_id', 'ip_address', 'user_agent']);
        });
    }
};
