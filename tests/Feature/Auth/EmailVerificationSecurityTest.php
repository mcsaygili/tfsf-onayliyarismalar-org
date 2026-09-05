<?php

namespace Tests\Feature\Auth;

use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Temsilci;
use App\Models\User;
use App\Services\VerifyAccountEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmailVerificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public static function panels(): array
    {
        return [
            'member' => ['web', '', User::class],
            'institution' => ['institution', 'institution.', InstitutionStaff::class],
            'representative' => ['temsilci', 'temsilci.', Temsilci::class],
            'jury' => ['juri', 'juri.', Juri::class],
        ];
    }

    #[DataProvider('panels')]
    public function test_signed_link_verifies_once_and_preserves_original_time_on_replay(string $guard, string $prefix, string $model): void
    {
        $account = $model::factory()->unverified()->create();
        $url = $this->url($prefix, $account);
        $this->actingAs($account, $guard)->get($url)->assertRedirect();
        $verified = $account->fresh()->email_verified_at;
        $this->assertNotNull($verified);
        $this->travel(2)->minutes();
        $this->get($url)->assertRedirect();
        $this->assertTrue($verified->eq($account->fresh()->email_verified_at));
    }

    #[DataProvider('panels')]
    public function test_expired_tampered_and_other_accounts_links_are_rejected(string $guard, string $prefix, string $model): void
    {
        $account = $model::factory()->unverified()->create();
        $other = $model::factory()->unverified()->create();
        $this->actingAs($account, $guard);
        $this->get($this->url($prefix, $account, -1))->assertForbidden();
        $this->get($this->url($prefix, $account).'&tampered=1')->assertForbidden();
        $this->get($this->url($prefix, $other))->assertForbidden();
        $this->assertNull($account->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
    }

    #[DataProvider('panels')]
    public function test_old_email_hash_is_rechecked_even_with_a_stale_authenticated_model(string $guard, string $prefix, string $model): void
    {
        $account = $model::factory()->unverified()->create();
        $hash = sha1($account->getEmailForVerification());
        $account->newQuery()->whereKey($account->id)->update(['email' => 'changed@example.test']);
        try {
            app(VerifyAccountEmail::class)->verify($account, $hash);
            $this->fail('A stale email hash must not verify the changed address.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertNull($account->fresh()->email_verified_at);
    }

    #[DataProvider('panels')]
    public function test_verification_resend_limit_follows_account_across_ips(string $guard, string $prefix, string $model): void
    {
        Notification::fake();
        $account = $model::factory()->unverified()->create();
        $this->actingAs($account, $guard);
        for ($i = 1; $i <= 6; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$i])
                ->post(route($prefix.'verification.send'))->assertRedirect()->assertSessionHas('status', 'verification-link-sent');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.7'])
            ->post(route($prefix.'verification.send'))->assertStatus(429);
        Notification::assertCount(6);
    }

    private function url(string $prefix, $account, int $minutes = 60): string
    {
        return URL::temporarySignedRoute($prefix.'verification.verify', now()->addMinutes($minutes), [
            'id' => $account->id, 'hash' => sha1($account->getEmailForVerification()),
        ]);
    }
}
