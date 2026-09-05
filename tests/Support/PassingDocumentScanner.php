<?php

namespace Tests\Support;

use App\Support\Documents\PdfDocumentScanner;

/** Test-only stand-in for preregistration workflow tests; quarantine/engine tests do not use it. */
class PassingDocumentScanner extends PdfDocumentScanner
{
    public function scan(string $path): array
    {
        return ['status' => 'clean', 'reason' => 'verified', 'engine' => 'Synthetic workflow test scanner'];
    }
}
