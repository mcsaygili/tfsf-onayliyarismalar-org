<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jury_invitations', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('sent_at');
            $table->timestamp('declined_at')->nullable()->after('accepted_at');
            $table->unsignedSmallInteger('send_count')->default(0)->after('locale');
        });

        Schema::create('jury_invitation_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('jury_invitation_id')->constrained('jury_invitations')->cascadeOnDelete();
            $table->string('action', 32);
            $table->nullableUuidMorphs('actor', 'jury_invitation_event_actor');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['jury_invitation_id', 'created_at'], 'jury_invitation_event_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jury_invitation_events');

        Schema::table('jury_invitations', function (Blueprint $table) {
            $table->dropColumn(['opened_at', 'declined_at', 'send_count']);
        });
    }
};
