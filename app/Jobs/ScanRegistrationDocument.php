<?php

namespace App\Jobs;

use App\Services\RegistrationDocumentScanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScanRegistrationDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 150;

    public array $backoff = [60, 300];

    public function __construct(public readonly string $documentId)
    {
        $this->onConnection('document_scans')->onQueue(config('registration-documents.queue'));
    }

    public function handle(RegistrationDocumentScanService $scans): void
    {
        if ($scans->scan($this->documentId) === 'error') {
            throw new \RuntimeException('Document scan unavailable; retained in quarantine.');
        }
    }
}
