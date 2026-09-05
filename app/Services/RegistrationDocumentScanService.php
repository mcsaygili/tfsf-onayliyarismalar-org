<?php

namespace App\Services;

use App\Models\CompetitionRegistrationDocument;
use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistrationDocumentScanService
{
    public function scan(string $documentId): string
    {
        $document = CompetitionRegistrationDocument::find($documentId);
        if (! $document) {
            return 'missing';
        }
        $competitionId = $document->registration->competition_id;
        $token = (string) Str::uuid();
        $claimed = DB::transaction(function () use ($document, $token, $competitionId) {
            CompetitionMutationLock::acquire($competitionId);
            $current = CompetitionRegistrationDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            if ($current->isTrusted() || ($current->scan_status === 'scanning' && $current->scan_started_at?->gt(now()->subSeconds(config('registration-documents.lease_seconds'))))) {
                return null;
            }
            $current->forceFill(['scan_status' => 'scanning', 'scan_token' => $token, 'scan_started_at' => now(), 'scan_attempts' => $current->scan_attempts + 1, 'scan_sha256' => null, 'scan_policy' => null, 'scan_reason' => null])->save();

            return $current;
        });
        if (! $claimed) {
            return 'skipped';
        }
        $disk = Storage::disk('local');
        $workPath = 'document-scan-work/'.$token.'/input.pdf';
        try {
            if (! $disk->exists($claimed->disk_path)) {
                $verdict = ['status' => 'rejected', 'reason' => 'file_missing', 'engine' => null];
            } elseif ($disk->size($claimed->disk_path) > 10 * 1024 * 1024 || $disk->size($claimed->disk_path) !== (int) $claimed->file_size_bytes) {
                $verdict = ['status' => 'rejected', 'reason' => 'checksum_mismatch', 'engine' => null];
            } else {
                $source = $disk->readStream($claimed->disk_path);
                try {
                    if (! is_resource($source) || ! $disk->put($workPath, $source)) {
                        throw new \RuntimeException('Document scan copy failed.');
                    }
                } finally {
                    if (is_resource($source)) {
                        fclose($source);
                    }
                }
                chmod($disk->path($workPath), 0600);
                if (! hash_equals($claimed->sha256, hash_file('sha256', $disk->path($workPath)))) {
                    $verdict = ['status' => 'rejected', 'reason' => 'checksum_mismatch', 'engine' => null];
                } else {
                    $verdict = app(PdfDocumentScanner::class)->scan($disk->path($workPath));
                    if (! hash_equals($claimed->sha256, hash_file('sha256', $disk->path($workPath)))) {
                        $verdict = ['status' => 'rejected', 'reason' => 'checksum_mismatch', 'engine' => null];
                    }
                }
            }
        } catch (\Throwable) {
            // Never expose a parser command, document content, filesystem path or daemon reply in UI/logs.
            $verdict = ['status' => 'error', 'reason' => 'scanner_failed', 'engine' => null];
        } finally {
            $disk->deleteDirectory('document-scan-work/'.$token);
        }
        if (! in_array($verdict['status'] ?? null, ['clean', 'rejected', 'error'], true)) {
            $verdict = ['status' => 'error', 'reason' => 'scanner_failed', 'engine' => null];
        }

        return DB::transaction(function () use ($claimed, $token, $verdict, $competitionId) {
            CompetitionMutationLock::acquire($competitionId);
            $current = CompetitionRegistrationDocument::whereKey($claimed->id)->lockForUpdate()->first();
            if (! $current || $current->scan_token !== $token || $current->sha256 !== $claimed->sha256) {
                return 'superseded';
            }
            $current->forceFill(['scan_status' => $verdict['status'], 'scan_reason' => $verdict['reason'], 'scan_engine' => $verdict['engine'], 'scanned_at' => now(), 'scan_sha256' => $claimed->sha256, 'scan_policy' => PdfDocumentScanner::POLICY, 'scan_token' => null])->save();
            $registration = $current->registration;
            $registration->events()->create(['event' => 'document_scan_'.$verdict['status'], 'version' => $registration->version, 'actor_type' => 'system:document-scanner', 'actor_id' => $token, 'context' => ['document_id' => $current->id, 'policy' => PdfDocumentScanner::POLICY, 'reason' => $verdict['reason'], 'sha256' => $claimed->sha256]]);

            return $verdict['status'];
        });
    }
}
