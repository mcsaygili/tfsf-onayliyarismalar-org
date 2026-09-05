<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AccountSecurityContext;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class SmsPasswordResetConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This process concurrency test requires MariaDB/MySQL.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName(), 'Use an isolated tfsf_testing database.');
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_two_simultaneous_requests_consume_a_code_only_once(): void
    {
        $user = User::factory()->create(['phone_number' => '5550000077']);
        DB::table('sms_password_reset_codes')->insert([
            'user_id' => $user->id, 'security_context' => app(AccountSecurityContext::class)->fingerprint($user), 'phone_number' => $user->phone_number, 'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10), 'created_at' => now(),
        ]);
        $payload = base64_encode(json_encode(['phone_number' => $user->phone_number, 'code' => '123456',
            'password' => 'ConcurrentPassword!123', 'password_confirmation' => 'ConcurrentPassword!123']));
        $workers = [];
        $gate = tempnam(sys_get_temp_dir(), 'tfsf-sms-gate-');
        unlink($gate);
        try {
            for ($i = 0; $i < 2; $i++) {
                $workers[$i] = new Process([PHP_BINARY, base_path('tests/Support/sms-reset-worker.php'), $payload, $gate], base_path(), [
                    'APP_ENV' => 'testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
                    'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'DB_URL' => '',
                    'DB_CONNECTION' => config('database.default'), 'DB_HOST' => config('database.connections.'.config('database.default').'.host'),
                    'DB_PORT' => (string) config('database.connections.'.config('database.default').'.port'),
                    'DB_DATABASE' => 'tfsf_testing', 'DB_USERNAME' => config('database.connections.'.config('database.default').'.username'),
                    'DB_PASSWORD' => config('database.connections.'.config('database.default').'.password'),
                ], null, 20);
                $workers[$i]->start();
            }
            foreach ($workers as $worker) {
                while (! str_contains($worker->getOutput(), 'ready')) {
                    $worker->checkTimeout();
                    if (! $worker->isRunning()) {
                        $this->fail($worker->getOutput().$worker->getErrorOutput());
                    }
                    usleep(10000);
                }
            }
            touch($gate);
            $statuses = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
                $this->assertMatchesRegularExpression('/status:\d+/', $worker->getOutput());
                preg_match('/status:(\d+)/', $worker->getOutput(), $matches);
                $statuses[] = (int) ($matches[1] ?? 0);
            }
            sort($statuses);
            $this->assertSame([302, 422], $statuses);
            $this->assertDatabaseCount('sms_password_reset_codes', 0);
            $this->assertTrue(Hash::check('ConcurrentPassword!123', $user->fresh()->password));
        } finally {
            if (is_file($gate)) {
                unlink($gate);
            }
            foreach ($workers as $worker) {
                $worker->stop();
            }
        }
    }
}
