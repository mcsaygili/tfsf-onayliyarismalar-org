<?php

namespace App\Jobs;

use App\Models\NotificationDispatch;
use App\Services\NotificationDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationDispatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly string $dispatchId) {}

    public function handle(NotificationDispatchService $service): void
    {
        $dispatch = NotificationDispatch::query()->find($this->dispatchId);
        if (! $dispatch || in_array($dispatch->status, ['sent', 'delivered', 'complained'], true)) {
            return;
        }

        if (! $service->sendAttempt($dispatch)) {
            $dispatch->refresh();
            if ($dispatch->attempts < $dispatch->max_attempts) {
                $this->release($service->retryDelaySeconds($dispatch->attempts));
            }
        }
    }
}
