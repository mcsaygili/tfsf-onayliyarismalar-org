<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $accounts = ['users', 'institution_staff', 'representatives', 'jurors', 'eys_users'];

    private array $challenges = ['password_reset_tokens', 'institution_staff_password_reset_tokens', 'representative_password_reset_tokens', 'juror_password_reset_tokens', 'eys_users_password_reset_tokens', 'sms_password_reset_codes'];

    public function up(): void
    {
        foreach ([...$this->accounts, 'institutions'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->char('security_stamp', 64)->nullable());
        }
        foreach ($this->accounts as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->char('remember_context', 64)->nullable());
            // Legacy cookies have no security context; require a fresh login.
            DB::table($table)->update(['remember_token' => null]);
        }
        foreach ($this->challenges as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->char('security_context', 64)->nullable());
        }
    }

    public function down(): void
    {
        // Older code cannot enforce bindings. Do not revive outstanding credentials.
        foreach ($this->challenges as $table) {
            DB::table($table)->delete();
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('security_context'));
        }
        foreach ($this->accounts as $table) {
            DB::table($table)->update(['remember_token' => null]);
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('remember_context'));
        }
        foreach ([...$this->accounts, 'institutions'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('security_stamp'));
        }
    }
};
