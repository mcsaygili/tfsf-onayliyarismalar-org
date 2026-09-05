<?php

return [
    'qpdf' => env('DOCUMENT_QPDF_BINARY', '/usr/bin/qpdf'),
    'clamdscan' => env('DOCUMENT_CLAMDSCAN_BINARY', '/usr/bin/clamdscan'),
    'clamd_config' => env('DOCUMENT_CLAMD_CONFIG', '/etc/clamav/clamd.conf'),
    'queue' => 'document-scans',
    'lease_seconds' => 300,
    'retry_seconds' => 300,
    'process_timeout' => 30,
    'max_json_bytes' => 8 * 1024 * 1024,
];
