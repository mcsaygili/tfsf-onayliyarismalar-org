<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemHealthService
{
    public const QUEUE_WORKER_HEARTBEAT = 'system-health:queue-worker-heartbeat';

    public const SCHEDULER_HEARTBEAT = 'system-health:scheduler-heartbeat';

    /** @return array<string, array{status: string, detail: string, checked_at: Carbon}> */
    public function checks(): array
    {
        $checkedAt = now();
        $checks = [];
        try {
            DB::select('select 1');
            $checks['database'] = $this->check('ok', 'Veritabanı bağlantısı çalışıyor.', $checkedAt);
        } catch (Throwable $exception) {
            $checks['database'] = $this->check('error', $exception->getMessage(), $checkedAt);
        }

        $writable = is_writable(storage_path('app'));
        $checks['storage'] = $this->check($writable ? 'ok' : 'error', $writable ? 'Özel dosya alanı yazılabilir.' : 'Storage yazma izni bulunmuyor.', $checkedAt);

        try {
            $waitingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $checks['queue_backlog'] = $this->check($failedJobs > 0 ? 'warning' : 'ok', $waitingJobs.' bekleyen, '.$failedJobs.' başarısız iş.', $checkedAt);
        } catch (Throwable $exception) {
            $checks['queue_backlog'] = $this->check('error', 'Kuyruk tabloları okunamadı: '.$exception->getMessage(), $checkedAt);
        }

        $checks['queue_worker'] = $this->heartbeatCheck(
            self::QUEUE_WORKER_HEARTBEAT,
            (int) config('operations.health.queue_worker_stale_after_seconds', 180),
            'Queue worker',
            $checkedAt,
        );
        $checks['scheduler'] = $this->heartbeatCheck(
            self::SCHEDULER_HEARTBEAT,
            (int) config('operations.health.scheduler_stale_after_seconds', 180),
            'Scheduler',
            $checkedAt,
        );
        $checks['mail'] = $this->check(filled(config('mail.default')) ? 'ok' : 'warning', 'Aktif sürücü: '.(config('mail.default') ?: 'tanımsız'), $checkedAt);

        return $checks;
    }

    public static function beatQueueWorker(): void
    {
        try {
            Cache::put(self::QUEUE_WORKER_HEARTBEAT, now()->toIso8601String(), now()->addDay());
        } catch (Throwable) {
            // Sağlık sinyali worker'ın gerçek işlerini durdurmamalıdır.
        }
    }

    public static function beatScheduler(): void
    {
        try {
            Cache::put(self::SCHEDULER_HEARTBEAT, now()->toIso8601String(), now()->addDay());
        } catch (Throwable) {
            // Sağlık sinyali zamanlanmış görevlerin çalışmasını durdurmamalıdır.
        }
    }

    /** @return array{status: string, detail: string, checked_at: Carbon} */
    private function heartbeatCheck(string $key, int $staleAfterSeconds, string $name, Carbon $checkedAt): array
    {
        try {
            $value = Cache::get($key);
        } catch (Throwable $exception) {
            return $this->check('error', $name.' heartbeat deposu okunamadı: '.$exception->getMessage(), $checkedAt);
        }
        if (blank($value)) {
            return $this->check('error', $name.' heartbeat henüz alınmadı.', $checkedAt);
        }

        $heartbeatAt = Carbon::parse($value);
        $age = (int) $heartbeatAt->diffInSeconds($checkedAt);
        $status = $age > $staleAfterSeconds ? 'error' : 'ok';
        $detail = $status === 'ok'
            ? $name.' çalışıyor. Son sinyal '.$age.' saniye önce alındı.'
            : $name.' sinyali gecikmiş. Son sinyal '.$age.' saniye önce alındı.';

        return $this->check($status, $detail, $checkedAt);
    }

    /** @return array{status: string, detail: string, checked_at: Carbon} */
    private function check(string $status, string $detail, Carbon $checkedAt): array
    {
        return ['status' => $status, 'detail' => $detail, 'checked_at' => $checkedAt];
    }
}
