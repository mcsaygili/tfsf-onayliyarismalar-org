<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AccountSecurityContext;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class AccountSecurityConcurrencyTest extends TestCase
{
    use DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This process concurrency test requires MariaDB/MySQL.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_simultaneous_email_and_sms_resets_only_change_password_once(): void
    {
        $user = User::factory()->create(['phone_number' => '5559000055']);
        $token = Password::broker('users')->createToken($user);
        DB::table('sms_password_reset_codes')->insert([
            'user_id' => $user->id, 'phone_number' => $user->phone_number, 'code_hash' => Hash::make('123456'),
            'security_context' => app(AccountSecurityContext::class)->fingerprint($user), 'expires_at' => now()->addMinutes(10), 'created_at' => now(),
        ]);
        $results = $this->simultaneousRequests(
            ['url' => route('password.store'), 'method' => 'POST', 'payload' => ['email' => $user->email, 'token' => $token,
                'password' => 'EmailWinner123!', 'password_confirmation' => 'EmailWinner123!']],
            ['url' => route('password.sms.verify'), 'method' => 'POST', 'payload' => ['phone_number' => $user->phone_number, 'code' => '123456',
                'password' => 'SmsWinner123!', 'password_confirmation' => 'SmsWinner123!']],
        );
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, array_sum(array_column($results, 'events')));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $expected = $results[0]['errors'] ? 'SmsWinner123!' : 'EmailWinner123!';
        $this->assertTrue(Hash::check($expected, $user->fresh()->password));
        $this->assertFalse(Password::broker('users')->tokenExists($user->fresh(), $token));
    }

    public function test_self_service_password_change_and_email_reset_cannot_both_succeed(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('users')->createToken($user);
        $results = $this->simultaneousRequests(
            ['url' => route('password.update'), 'method' => 'PUT', 'guard' => 'web', 'user_id' => $user->id,
                'payload' => ['current_password' => 'password', 'password' => 'SelfServiceWinner123!', 'password_confirmation' => 'SelfServiceWinner123!']],
            ['url' => route('password.store'), 'method' => 'POST', 'payload' => ['email' => $user->email, 'token' => $token,
                'password' => 'ResetWinner123!', 'password_confirmation' => 'ResetWinner123!']],
        );
        $this->assertSame(302, $results[1]['status']);
        $resetSucceeded = ! $results[1]['errors'];
        $selfSucceeded = $results[0]['status'] === 302 && ! $results[0]['errors'];
        // A rejected stale panel session can also redirect to login with an error.
        $this->assertNotSame($resetSucceeded, $selfSucceeded);
        $this->assertTrue(Hash::check($resetSucceeded ? 'ResetWinner123!' : 'SelfServiceWinner123!', $user->fresh()->password));
        $this->assertSame($resetSucceeded ? 1 : 0, array_sum(array_column($results, 'events')));
    }

    public function test_failed_revocation_rolls_back_restriction_without_replaying_mutated_model(): void
    {
        $user = User::factory()->create();
        $stamp = $user->security_stamp;
        $failOnce = true;
        Event::listen('eloquent.updating: '.User::class, function () use (&$failOnce) {
            if ($failOnce) {
                $failOnce = false;
                throw new \PDOException('Deadlock found when trying to get lock; try restarting transaction', 40001);
            }
        });
        try {
            $user->restrictions()->create(['type' => 'account', 'reason' => 'Injected database failure', 'starts_at' => now()]);
            $this->fail('A failed write must not report a successful restriction.');
        } catch (\PDOException $exception) {
            $this->assertSame(40001, $exception->getCode());
        }
        $this->assertDatabaseCount('member_restrictions', 0);
        $this->assertSame($stamp, $user->fresh()->security_stamp);
    }
}
