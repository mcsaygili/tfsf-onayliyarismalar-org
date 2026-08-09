<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('temsilci_password_reset_tokens', 'representative_password_reset_tokens');
    }

    public function down(): void
    {
        Schema::rename('representative_password_reset_tokens', 'temsilci_password_reset_tokens');
    }
};
