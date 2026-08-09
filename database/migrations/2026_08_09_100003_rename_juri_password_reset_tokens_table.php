<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('juri_password_reset_tokens', 'juror_password_reset_tokens');
    }

    public function down(): void
    {
        Schema::rename('juror_password_reset_tokens', 'juri_password_reset_tokens');
    }
};
