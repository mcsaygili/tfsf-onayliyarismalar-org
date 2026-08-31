<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_template_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notification_template_id')->constrained(indexName: 'notification_template_translation_fk')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('subject');
            $table->string('greeting')->nullable();
            $table->text('body');
            $table->string('action_label')->nullable();
            $table->timestamps();
            $table->unique(['notification_template_id', 'locale'], 'notification_template_locale_unique');
        });

        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 80);
            $table->nullableUuidMorphs('recipient', 'notification_dispatch_recipient_index');
            $table->string('recipient_email')->nullable();
            $table->string('locale', 5)->default('tr');
            $table->string('template_key', 80);
            $table->string('status', 24)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedSmallInteger('manual_retry_count')->default(0);
            $table->longText('payload')->nullable();
            $table->text('last_error')->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->foreignUuid('last_retried_by')->nullable()->constrained('eys_users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'next_retry_at'], 'notification_dispatch_retry_index');
            $table->index(['competition_id', 'created_at'], 'notification_dispatch_timeline_index');
        });

        Schema::table('mail_send_logs', function (Blueprint $table) {
            $table->foreignUuid('notification_dispatch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignUuid('competition_id')->nullable()->after('notification_dispatch_id')->constrained()->nullOnDelete();
            $table->string('locale', 5)->nullable()->after('provider');
            $table->string('template_key', 80)->nullable()->after('locale');
            $table->unsignedTinyInteger('attempt_number')->default(1)->after('template_key');
            $table->timestamp('sent_at')->nullable()->after('error');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
        });

        Schema::table('mail_events', function (Blueprint $table) {
            $table->string('provider_event_id')->nullable()->unique()->after('id');
        });

        $now = now();
        foreach ($this->templates() as $template) {
            $templateId = (string) Str::uuid();
            DB::table('notification_templates')->insert([
                'id' => $templateId,
                'key' => $template['key'],
                'name' => $template['name'],
                'description' => $template['description'],
                'variables' => json_encode($template['variables'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($template['translations'] as $locale => $translation) {
                DB::table('notification_template_translations')->insert([
                    'id' => (string) Str::uuid(),
                    'notification_template_id' => $templateId,
                    'locale' => $locale,
                    'subject' => $translation['subject'],
                    'greeting' => $translation['greeting'],
                    'body' => $translation['body'],
                    'action_label' => $translation['action_label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('mail_events', function (Blueprint $table) {
            $table->dropUnique(['provider_event_id']);
            $table->dropColumn('provider_event_id');
        });

        Schema::table('mail_send_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('notification_dispatch_id');
            $table->dropConstrainedForeignId('competition_id');
            $table->dropColumn(['locale', 'template_key', 'attempt_number', 'sent_at', 'delivered_at', 'failed_at']);
        });

        Schema::dropIfExists('notification_dispatches');
        Schema::dropIfExists('notification_template_translations');
        Schema::dropIfExists('notification_templates');
    }

    /** @return array<int, array<string, mixed>> */
    private function templates(): array
    {
        return [
            [
                'key' => 'jury_invitation',
                'name' => 'Jüri Daveti',
                'description' => 'Kurum tarafından davet edilen jüri adayına gönderilir.',
                'variables' => ['name', 'institution', 'competition', 'expiry'],
                'translations' => [
                    'tr' => ['subject' => 'Jüri üyeliği daveti', 'greeting' => 'Merhaba {{name}},', 'body' => '{{institution}} tarafından düzenlenen {{competition}} yarışmasında jüri üyesi olarak davet edildiniz.\n\nDaveti {{expiry}} tarihine kadar yanıtlayabilirsiniz.', 'action_label' => 'Daveti görüntüle'],
                    'en' => ['subject' => 'Jury member invitation', 'greeting' => 'Hello {{name}},', 'body' => 'You have been invited to serve as a jury member for {{competition}}, organised by {{institution}}.\n\nYou can respond to this invitation until {{expiry}}.', 'action_label' => 'View invitation'],
                ],
            ],
            [
                'key' => 'jury_evaluation_deadline',
                'name' => 'Jüri Değerlendirme Son Tarihi',
                'description' => 'Değerlendirmesini tamamlamamış jüri üyelerine gönderilir.',
                'variables' => ['name', 'competition', 'deadline'],
                'translations' => [
                    'tr' => ['subject' => 'Jüri değerlendirme süresi yaklaşıyor', 'greeting' => 'Merhaba {{name}},', 'body' => '{{competition}} yarışmasındaki jüri değerlendirmeniz henüz tamamlanmadı.\n\nSon tarih: {{deadline}}', 'action_label' => 'Değerlendirmeye git'],
                    'en' => ['subject' => 'Jury evaluation deadline is approaching', 'greeting' => 'Hello {{name}},', 'body' => 'Your jury evaluation for {{competition}} has not been completed yet.\n\nDeadline: {{deadline}}', 'action_label' => 'Open evaluation'],
                ],
            ],
            [
                'key' => 'competition_results_member',
                'name' => 'Üye Sonuç Bildirimi',
                'description' => 'Sonuçlar yayımlandığında yarışmaya katılan üyelere gönderilir.',
                'variables' => ['name', 'competition'],
                'translations' => [
                    'tr' => ['subject' => 'Yarışma sonuçları yayımlandı', 'greeting' => 'Merhaba {{name}},', 'body' => '{{competition}} yarışmasının sonuçları yayımlandı.', 'action_label' => 'Sonuçları görüntüle'],
                    'en' => ['subject' => 'Competition results published', 'greeting' => 'Hello {{name}},', 'body' => 'The results of {{competition}} have been published.', 'action_label' => 'View results'],
                ],
            ],
            [
                'key' => 'competition_results_jury',
                'name' => 'Jüri Sonuç Bildirimi',
                'description' => 'Sonuçlar yayımlandığında görevli jüri üyelerine gönderilir.',
                'variables' => ['name', 'competition'],
                'translations' => [
                    'tr' => ['subject' => 'Yarışma sonuçları yayımlandı', 'greeting' => 'Merhaba {{name}},', 'body' => '{{competition}} yarışmasının sonuçları yayımlandı.', 'action_label' => 'Sonuçları görüntüle'],
                    'en' => ['subject' => 'Competition results published', 'greeting' => 'Hello {{name}},', 'body' => 'The results of {{competition}} have been published.', 'action_label' => 'View results'],
                ],
            ],
        ];
    }
};
