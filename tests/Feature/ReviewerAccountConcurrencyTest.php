<?php

namespace Tests\Feature;

use App\Models\Temsilci;
use App\Services\CompetitionRegistrationService;
use App\Services\RegistrationExceptionService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\Concerns\CreatesRegistrationException;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class ReviewerAccountConcurrencyTest extends TestCase
{
    use CreatesRegistrationException, DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MariaDB row-lock observation.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public static function accountChanges(): array
    {
        return ['photo deactivate' => ['photo', false], 'photo reactivate' => ['photo', true], 'registration deactivate' => ['registration', false], 'registration reactivate' => ['registration', true], 'direct deactivate' => ['direct', false], 'direct reactivate' => ['direct', true]];
    }

    #[DataProvider('accountChanges')]
    public function test_decision_waits_for_account_change_and_rejects_old_authority(string $operation, bool $reactivate): void
    {
        $f = $this->exceptionFixture(0);
        $actor = Temsilci::factory()->create();
        $f['competition']->forceFill(['representative_id' => $actor->id])->save();
        $approval = $f['submission']->approvals()->create(['approval_type' => 'representative', 'status' => 'pending', 'sequence' => 1]);
        $f['competition']->update(['registration_reviewer' => 'representative']);
        $f['registration']->update(['reviewer' => 'representative']);
        if ($operation === 'registration') {
            app(CompetitionRegistrationService::class)->submit($f['registration'], $f['member'], 1);
        }
        if ($operation === 'direct') {
            app(RegistrationExceptionService::class)->setGrant($f['competition'], $f['manager'], $actor, 0, true, 'Synthetic direct approval permission.');
        }
        $input = ['guard' => 'temsilci', 'user_id' => $actor->id, 'method' => 'POST'] + match ($operation) {
            'photo' => ['url' => route('temsilci.participant-submissions.decide', $approval), 'payload' => ['decision' => 'approve']],
            'registration' => ['url' => route('temsilci.registrations.decide', $f['registration']), 'payload' => ['version' => 2, 'decision' => 'approved']],
            'direct' => ['url' => route('temsilci.registrations.direct.store', $f['competition']), 'payload' => ['version' => 1, 'user_id' => $f['member']->id, 'grant_version' => 1, 'waive_documents' => 0, 'reason' => 'Synthetic verification evidence.']],
        };
        $database = config('database.connections.'.config('database.default'));
        $gate = tempnam(sys_get_temp_dir(), 'tfsf-account-gate-');
        unlink($gate);
        $worker = new Process([PHP_BINARY, base_path('tests/Support/account-http-worker.php'), base64_encode(json_encode($input)), $gate], base_path(), [
            'APP_ENV' => 'testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'DB_URL' => '',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $database['host'], 'DB_PORT' => (string) $database['port'], 'DB_DATABASE' => 'tfsf_testing', 'DB_USERNAME' => $database['username'], 'DB_PASSWORD' => $database['password'],
        ], null, 20);
        try {
            DB::beginTransaction();
            $current = Temsilci::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $current->update(['status' => false]);
            if ($reactivate) {
                $current->update(['status' => true]);
            }
            // The uncommitted update is invisible to HTTP authentication/preflight reads.
            $worker->start();
            $deadline = microtime(true) + 12;
            while (! str_contains($worker->getOutput(), 'ready')) {
                $worker->checkTimeout();
                if (! $worker->isRunning()) {
                    $this->fail($worker->getOutput().$worker->getErrorOutput());
                }
                if (microtime(true) > $deadline) {
                    $this->fail('Worker did not reach the start gate.');
                }
                usleep(10000);
            }
            preg_match('/connection:(\d+)/', $worker->getOutput(), $matches);
            $this->assertNotEmpty($matches[1]);
            touch($gate);
            $waiting = false;
            while (microtime(true) < $deadline && $worker->isRunning()) {
                // Observe this worker's locking read while the parent owns the account row.
                // The request must reach account authorization before the change commits.
                $query = DB::selectOne('SELECT INFO FROM information_schema.PROCESSLIST WHERE ID = ?', [(int) $matches[1]])?->INFO;
                $waiting = is_string($query)
                    && str_contains($query, 'from `representatives`')
                    && str_contains($query, 'for update');
                if ($waiting) {
                    break;
                }
                usleep(50000);
            }
            $this->assertTrue($waiting, 'Approval did not wait on reviewer authority: '.$worker->getOutput().$worker->getErrorOutput());
            DB::commit();
            $worker->wait();
            $this->assertTrue($worker->isSuccessful(), $worker->getOutput().$worker->getErrorOutput());
            preg_match('/result:(.+)/', $worker->getOutput(), $result);
            $this->assertNotEmpty($result[1]);
            $response = json_decode($result[1], true);
            $this->assertSame(404, $response['status']);
            $this->assertSame('pending', $approval->fresh()->status->value);
            $this->assertSame(0, $f['submission']->entry->events()->count());
            $this->assertSame($operation === 'registration' ? 'pending' : 'draft', $f['registration']->fresh()->status);
            $this->assertSame($operation === 'registration' ? 2 : 1, $f['registration']->fresh()->version);
            $this->assertSame($reactivate, $actor->fresh()->status);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $worker->stop();
            if (is_file($gate)) {
                unlink($gate);
            }
        }
    }
}
