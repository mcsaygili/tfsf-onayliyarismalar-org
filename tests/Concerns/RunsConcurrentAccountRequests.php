<?php

namespace Tests\Concerns;

use Symfony\Component\Process\Process;

trait RunsConcurrentAccountRequests
{
    private function simultaneousRequests(array $input, ?array $secondInput = null): array
    {
        $gate = tempnam(sys_get_temp_dir(), 'tfsf-account-gate-');
        unlink($gate);
        $workers = [];
        try {
            $database = config('database.connections.'.config('database.default'));
            for ($i = 0; $i < 2; $i++) {
                $workers[$i] = new Process([PHP_BINARY, base_path('tests/Support/account-http-worker.php'), base64_encode(json_encode($i === 1 && $secondInput !== null ? $secondInput : $input)), $gate], base_path(), [
                    'APP_ENV' => 'testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
                    'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'DB_URL' => '',
                    'DB_CONNECTION' => 'mysql', 'DB_HOST' => $database['host'], 'DB_PORT' => (string) $database['port'],
                    'DB_DATABASE' => 'tfsf_testing', 'DB_USERNAME' => $database['username'], 'DB_PASSWORD' => $database['password'],
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
            $results = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
                $this->assertMatchesRegularExpression('/result:(.+)/', $worker->getOutput());
                preg_match('/result:(.+)/', $worker->getOutput(), $matches);
                $results[] = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($workers as $worker) {
                $worker->stop();
            }
            if (is_file($gate)) {
                unlink($gate);
            }
        }
    }
}
