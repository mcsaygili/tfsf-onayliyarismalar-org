<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function tables(): array
    {
        return [
            'password_reset_tokens' => 'users',
            'institution_staff_password_reset_tokens' => 'institution_staff',
            'representative_password_reset_tokens' => 'representatives',
            'juror_password_reset_tokens' => 'jurors',
            'eys_users_password_reset_tokens' => 'eys_users',
        ];
    }

    public function up(): void
    {
        foreach ($this->tables() as $table => $users) {
            Schema::table($table, function (Blueprint $blueprint) use ($users) {
                // Existing email-only tokens require a new reset request.
                $blueprint->foreignUuid('user_id')->nullable()->constrained($users)->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table => $users) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropConstrainedForeignId('user_id'));
        }
    }
};
