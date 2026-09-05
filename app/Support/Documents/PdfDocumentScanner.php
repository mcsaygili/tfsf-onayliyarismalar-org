<?php

namespace App\Support\Documents;

use Symfony\Component\Process\Process;

class PdfDocumentScanner
{
    public const POLICY = 'qpdf-clamav-pdf-v1';

    // PDF name objects are examined after qpdf resolves object streams and PDF syntax.
    private const BLOCKED_NAMES = ['/OpenAction', '/AA', '/A', '/JS', '/JavaScript', '/Launch', '/EmbeddedFiles', '/EF', '/AF', '/RichMedia', '/XFA', '/SubmitForm', '/ImportData', '/GoToR', '/GoToE', '/URI', '/Sound', '/Movie', '/3D'];

    public function scan(string $path): array
    {
        $qpdf = config('registration-documents.qpdf');
        $clamdscan = config('registration-documents.clamdscan');
        if (! is_executable($qpdf) || ! is_executable($clamdscan) || ! is_readable(config('registration-documents.clamd_config'))) {
            return $this->result('error', 'scanner_unavailable');
        }
        [$code] = $this->run([$qpdf, '--check', $path]);
        if ($code !== 0) {
            return $this->result(in_array($code, [2, 3], true) ? 'rejected' : 'error', in_array($code, [2, 3], true) ? 'pdf_structure' : 'scanner_failed');
        }
        [$code, $json] = $this->run([$qpdf, '--json=2', '--json-stream-data=none', $path]);
        if ($code !== 0) {
            return $this->result(in_array($code, [2, 3], true) ? 'rejected' : 'error', in_array($code, [2, 3], true) ? 'pdf_structure' : 'scanner_failed');
        }
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! isset($data['qpdf'][1], $data['pages'], $data['encrypt']['encrypted']) || ! is_array($data['pages']) || count($data['pages']) === 0) {
            return $this->result('rejected', 'pdf_structure');
        }
        if ($data['encrypt']['encrypted']) {
            return $this->result('rejected', 'pdf_encrypted');
        }
        if ($this->containsActiveContent($data['qpdf'][1])) {
            return $this->result('rejected', 'pdf_active_content');
        }
        $arguments = [$clamdscan, '--config-file='.config('registration-documents.clamd_config')];
        [$code, $version] = $this->run([...$arguments, '--version']);
        if ($code !== 0 || ! str_starts_with(trim($version), 'ClamAV ')) {
            return $this->result('error', 'scanner_unavailable');
        }
        [$code] = $this->run([...$arguments, '--stream', '--no-summary', $path]);

        return match ($code) {
            0 => $this->result('clean', 'verified', trim($version)),
            1 => $this->result('rejected', 'malware_detected', trim($version)),
            default => $this->result('error', 'scanner_failed'),
        };
    }

    private function containsActiveContent(array $value): bool
    {
        foreach ($value as $key => $item) {
            if (in_array($key, self::BLOCKED_NAMES, true) || (is_string($item) && in_array($item, self::BLOCKED_NAMES, true))) {
                return true;
            }
            if (is_array($item) && $this->containsActiveContent($item)) {
                return true;
            }
        }

        return false;
    }

    private function run(array $arguments): array
    {
        $process = new Process($arguments);
        $process->setTimeout(config('registration-documents.process_timeout'));
        $output = '';
        $bytes = 0;
        try {
            $code = $process->run(function (string $type, string $buffer) use (&$output, &$bytes): void {
                $bytes += strlen($buffer);
                if ($bytes > config('registration-documents.max_json_bytes')) {
                    throw new \RuntimeException('Document scanner output exceeded its limit.');
                }
                if ($type === Process::OUT) {
                    $output .= $buffer;
                }
            });

            return [$code, $output];
        } finally {
            if ($process->isRunning()) {
                $process->stop(0);
            }
        }
    }

    private function result(string $status, string $reason, ?string $engine = null): array
    {
        return ['status' => $status, 'reason' => $reason, 'engine' => $engine ? mb_substr($engine, 0, 255) : null];
    }
}
