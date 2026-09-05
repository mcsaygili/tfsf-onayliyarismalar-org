<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class EmailAccountConcurrencyTest extends TestCase
{
    use DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This process concurrency test requires MariaDB/MySQL.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName(), 'Use an isolated tfsf_testing database.');
        $this->assertTrue(app()->environment('testing'));
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_simultaneous_email_resets_consume_token_and_emit_event_once(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $token = Password::broker($broker)->createToken($account);
        $results = $this->simultaneousRequests(['url' => route($prefix.'password.store'), 'method' => 'POST', 'payload' => [
            'email' => $account->email, 'token' => $token, 'password' => 'ConcurrentSecure123!', 'password_confirmation' => 'ConcurrentSecure123!',
        ]]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertSame(1, array_sum(array_column($results, 'events')));
        $this->assertTrue(Hash::check('ConcurrentSecure123!', $account->fresh()->password));
        $this->assertDatabaseCount(config('auth.passwords.'.$broker.'.table'), 0);
    }

    #[DataProviderExternal(EmailVerificationSecurityTest::class, 'panels')]
    public function test_simultaneous_verifications_emit_event_once(string $guard, string $prefix, string $model): void
    {
        $account = $model::factory()->unverified()->create();
        $url = URL::temporarySignedRoute($prefix.'verification.verify', now()->addHour(), [
            'id' => $account->id, 'hash' => sha1($account->getEmailForVerification()),
        ]);
        $results = $this->simultaneousRequests(['url' => $url, 'method' => 'GET', 'guard' => $guard, 'user_id' => $account->id]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, array_sum(array_column($results, 'events')));
        $this->assertNotNull($account->fresh()->email_verified_at);
    }
}
