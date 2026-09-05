<?php

namespace App\Console\Commands;

use App\Models\CompetitionRegistrationDocument;
use App\Services\RegistrationDocumentScanService;
use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Console\Command;

class ScanRegistrationDocuments extends Command
{
    protected $signature = 'tfsf:scan-registration-documents {--limit=20} {--document= : Retry this document, including a previously rejected version}';

    protected $description = 'Scan pending registration PDFs and recover interrupted scans without exposing unverified files';

    public function handle(RegistrationDocumentScanService $scans): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if (! $limit || $limit < 1 || $limit > 100) {
            $this->error('Limit must be between 1 and 100.');

            return self::INVALID;
        }
        $query = CompetitionRegistrationDocument::query();
        if ($id = $this->option('document')) {
            $query->whereKey($id);
            if (! $query->exists()) {
                $this->error('Document not found.');

                return self::FAILURE;
            }
        } else {
            $query->where(fn ($q) => $q->where('scan_status', 'pending')
                ->orWhere(fn ($q) => $q->where('scan_status', 'error')->where(fn ($q) => $q->whereNull('scanned_at')->orWhere('scanned_at', '<=', now()->subSeconds(config('registration-documents.retry_seconds')))))
                ->orWhere(fn ($q) => $q->where('scan_status', 'scanning')->where(fn ($q) => $q->whereNull('scan_started_at')->orWhere('scan_started_at', '<=', now()->subSeconds(config('registration-documents.lease_seconds')))))
                ->orWhere(fn ($q) => $q->where('scan_status', 'clean')->where(fn ($q) => $q->whereNull('scan_policy')->orWhere('scan_policy', '!=', PdfDocumentScanner::POLICY)->orWhereNull('scanned_at')->orWhereNull('scan_sha256')->orWhereColumn('scan_sha256', '!=', 'sha256'))));
        }
        $failures = 0;
        foreach ($query->orderBy('id')->limit($limit)->pluck('id') as $documentId) {
            $result = $scans->scan($documentId);
            $this->line($documentId.' '.$result);
            if (in_array($result, ['error', 'rejected'], true)) {
                $failures++;
            }
        }

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
