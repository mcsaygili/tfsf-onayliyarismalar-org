<?php

return [
    'sla' => [
        'review_hours' => (int) env('OPERATIONS_REVIEW_SLA_HOURS', 48),
        'correction_hours' => (int) env('OPERATIONS_CORRECTION_SLA_HOURS', 72),
    ],
    'health' => [
        'queue_worker_stale_after_seconds' => (int) env('QUEUE_WORKER_STALE_AFTER_SECONDS', 180),
        'scheduler_stale_after_seconds' => (int) env('SCHEDULER_STALE_AFTER_SECONDS', 180),
    ],
];
