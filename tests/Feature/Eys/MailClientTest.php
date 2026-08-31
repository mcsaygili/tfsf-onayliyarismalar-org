<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Jobs\SendNotificationDispatchJob;
use App\Models\EysUser;
use App\Models\MailEvent;
use App\Models\MailSendLog;
use App\Models\MailSetting;
use App\Models\NotificationDispatch;
use App\Models\NotificationTemplate;
use App\Models\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MailClientTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.mail_client.view', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.mail_client.view');

        return $user;
    }

    private function admin(): EysUser
    {
        $user = $this->viewer();

        Permission::firstOrCreate(['name' => 'eys.mail_client.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.mail_client.manage');

        return $user;
    }

    public function test_gosterge_paneli_goruntulenebilir(): void
    {
        $user = $this->viewer();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.dashboard'));

        $response->assertOk();
    }

    public function test_sadece_view_izniyle_ayarlar_sayfasina_erisilemez(): void
    {
        $user = $this->viewer();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.settings'));

        $response->assertForbidden();
    }

    public function test_ayarlar_guncellenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->patch(route('eys.mail-client.settings.update'), [
            'daily_quota' => '500',
            'rate_per_second' => '5',
            'enabled' => '1',
        ]);

        $response->assertRedirect(route('eys.mail-client.settings'));

        $settings = MailSetting::current();
        $this->assertSame(500, $settings->daily_quota);
        $this->assertSame(5, $settings->rate_per_second);
        $this->assertTrue($settings->enabled);
        $this->assertSame($user->id, $settings->updated_by);
    }

    public function test_test_epostasi_gonderilebilir(): void
    {
        Mail::fake();

        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.mail-client.test.send'), [
            'to' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Merhaba',
        ]);

        $response->assertRedirect(route('eys.mail-client.test'));
        $response->assertSessionHas('status');
    }

    public function test_gunlukler_sayfasi_goruntulenebilir(): void
    {
        $user = $this->viewer();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.logs'));

        $response->assertOk();
    }

    public function test_etkinlik_sayfasi_goruntulenebilir(): void
    {
        $user = $this->viewer();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.activity'));

        $response->assertOk();
    }

    public function test_izinsiz_kullanici_mail_istemcisine_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.dashboard'));

        $response->assertForbidden();
    }

    public function test_gunlukler_tarih_araligina_gore_filtrelenebilir(): void
    {
        $user = $this->viewer();

        $eski = MailSendLog::create(['to' => 'eski@example.com', 'status' => 'sent']);
        $eski->forceFill(['created_at' => CarbonImmutable::now()->subDays(10)])->save();

        $yeni = MailSendLog::create(['to' => 'yeni@example.com', 'status' => 'sent']);
        $yeni->forceFill(['created_at' => CarbonImmutable::now()->subDay()])->save();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.logs', [
            'date_from' => CarbonImmutable::now()->subDays(3)->format('Y-m-d'),
            'date_to' => CarbonImmutable::now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('yeni@example.com');
        $response->assertDontSee('eski@example.com');
    }

    public function test_gunlukler_gecersiz_tarih_filtresi_hata_vermez(): void
    {
        $user = $this->viewer();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.logs', [
            'date_from' => 'gecersiz-tarih',
        ]));

        $response->assertOk();
    }

    public function test_etkinlik_olay_turune_gore_filtrelenebilir(): void
    {
        $user = $this->viewer();

        $log = MailSendLog::create(['to' => 'alici@example.com', 'status' => 'sent']);
        MailEvent::create(['mail_send_log_id' => $log->id, 'event_type' => 'delivered', 'payload' => []]);
        MailEvent::create(['mail_send_log_id' => $log->id, 'event_type' => 'bounced', 'payload' => []]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.activity', [
            'event_type' => 'delivered',
        ]));

        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertSame(1, $events->total());
        $this->assertSame('delivered', $events->first()->event_type);
    }

    public function test_etkinlik_tarih_araligina_gore_filtrelenebilir(): void
    {
        $user = $this->viewer();

        $log = MailSendLog::create(['to' => 'alici@example.com', 'status' => 'sent']);

        $eski = MailEvent::create(['mail_send_log_id' => $log->id, 'event_type' => 'opened', 'payload' => []]);
        $eski->forceFill(['created_at' => CarbonImmutable::now()->subDays(10)])->save();

        $yeni = MailEvent::create(['mail_send_log_id' => $log->id, 'event_type' => 'clicked', 'payload' => []]);
        $yeni->forceFill(['created_at' => CarbonImmutable::now()->subDay()])->save();

        $response = $this->actingAs($user, 'eys')->get(route('eys.mail-client.activity', [
            'date_from' => CarbonImmutable::now()->subDays(3)->format('Y-m-d'),
            'date_to' => CarbonImmutable::now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertSame(1, $events->total());
        $this->assertSame('clicked', $events->first()->event_type);
    }

    public function test_bildirim_sablonlari_turkce_ve_ingilizce_guncellenebilir(): void
    {
        $user = $this->admin();
        $template = NotificationTemplate::query()->where('key', 'jury_invitation')->firstOrFail();

        $response = $this->actingAs($user, 'eys')->patch(route('eys.mail-client.templates.update', $template), [
            'is_active' => '1',
            'translations' => [
                'tr' => ['subject' => 'TR konu {{competition}}', 'greeting' => 'Merhaba {{name}}', 'body' => 'TR mesaj', 'action_label' => 'Aç'],
                'en' => ['subject' => 'EN subject {{competition}}', 'greeting' => 'Hello {{name}}', 'body' => 'EN message', 'action_label' => 'Open'],
            ],
        ]);

        $response->assertRedirect(route('eys.mail-client.templates.index'));
        $this->assertDatabaseHas('notification_template_translations', [
            'notification_template_id' => $template->id,
            'locale' => 'en',
            'subject' => 'EN subject {{competition}}',
        ]);
    }

    public function test_basarisiz_teslimatlar_filtrelenebilir_ve_yeniden_kuyruga_alinabilir(): void
    {
        Queue::fake();
        $user = $this->admin();
        $dispatch = NotificationDispatch::create([
            'type' => 'jury_invitation',
            'recipient_email' => 'failed@example.com',
            'locale' => 'tr',
            'template_key' => 'jury_invitation',
            'status' => 'failed',
            'attempts' => 3,
            'max_attempts' => 3,
            'payload' => [],
        ]);
        MailSendLog::create(['notification_dispatch_id' => $dispatch->id, 'to' => 'failed@example.com', 'status' => 'failed']);
        MailSendLog::create(['to' => 'sent@example.com', 'status' => 'sent']);

        $this->actingAs($user, 'eys')->get(route('eys.mail-client.logs', ['failed_delivery' => 1]))
            ->assertOk()->assertSee('failed@example.com')->assertDontSee('sent@example.com');

        $this->actingAs($user, 'eys')->post(route('eys.mail-client.retry', $dispatch))
            ->assertRedirect();

        $dispatch->refresh();
        $this->assertSame('pending', $dispatch->status);
        $this->assertSame(1, $dispatch->manual_retry_count);
        $this->assertSame($user->id, $dispatch->last_retried_by);
        Queue::assertPushed(SendNotificationDispatchJob::class, fn ($job) => $job->dispatchId === $dispatch->id);
    }
}
