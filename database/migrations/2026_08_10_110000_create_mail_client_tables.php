<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mail İstemcisi (Resend) — tekil ayar satırı + her gönderimin kaydı +
 * Resend webhook (Svix imzalı) olaylarının kaydı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('daily_quota')->nullable();
            $table->unsignedInteger('rate_per_second')->nullable();
            $table->boolean('enabled')->default(true);
            $table->foreignUuid('updated_by')->nullable()->constrained('eys_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mail_send_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('mailable')->nullable();
            $table->string('to');
            $table->string('subject')->nullable();
            $table->string('status')->default('sent');
            $table->string('provider')->default('resend');
            $table->string('provider_message_id')->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('mail_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mail_send_log_id')->nullable()->constrained('mail_send_logs')->nullOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_events');
        Schema::dropIfExists('mail_send_logs');
        Schema::dropIfExists('mail_settings');
    }
};
