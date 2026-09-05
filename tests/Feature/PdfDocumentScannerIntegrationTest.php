<?php

namespace Tests\Feature;

use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Tests\Support\PdfScanFixtures;
use Tests\TestCase;

#[Group('document-scanner-integration')]
class PdfDocumentScannerIntegrationTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('DOCUMENT_SCAN_INTEGRATION') !== '1') {
            $this->markTestSkipped('Requires isolated qpdf and ClamAV daemon with the documented synthetic test signature.');
        }
        $this->directory = sys_get_temp_dir().'/tfsf-pdf-scan-'.Str::uuid();
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            File::deleteDirectory($this->directory);
        }
        parent::tearDown();
    }

    private function pdf(string $catalog = '', string $marker = 'Synthetic document'): string
    {
        $path = $this->directory.'/'.Str::uuid().'.pdf';
        file_put_contents($path, PdfScanFixtures::bytes($catalog, $marker));

        return $path;
    }

    public function test_normal_pdf_is_clean_after_both_real_tools(): void
    {
        $result = app(PdfDocumentScanner::class)->scan($this->pdf());
        $this->assertSame('clean', $result['status']);
        $this->assertStringStartsWith('ClamAV ', $result['engine']);
    }

    public function test_active_content_hidden_in_compressed_objects_and_escaped_names_is_rejected(): void
    {
        $source = $this->pdf('/Open#41ction << /S /Java#53cript /JS (void\(0\)) >>');
        $compressed = $this->directory.'/compressed.pdf';
        $process = new Process([config('registration-documents.qpdf'), '--object-streams=generate', $source, $compressed]);
        $this->assertSame(0, $process->run(), $process->getErrorOutput());
        $result = app(PdfDocumentScanner::class)->scan($compressed);
        $this->assertSame('rejected', $result['status']);
        $this->assertSame('pdf_active_content', $result['reason']);
    }

    public function test_embedded_files_and_xfa_are_rejected(): void
    {
        foreach (['/Names << /EmbeddedFiles << /Names [] >> >>', '/AcroForm << /XFA (payload) >>'] as $catalog) {
            $result = app(PdfDocumentScanner::class)->scan($this->pdf($catalog));
            $this->assertSame('rejected', $result['status']);
            $this->assertSame('pdf_active_content', $result['reason']);
        }
    }

    public function test_plain_acroform_is_not_rejected_just_for_being_a_form(): void
    {
        $this->assertSame('clean', app(PdfDocumentScanner::class)->scan($this->pdf('/AcroForm << /Fields [] >>'))['status']);
    }

    public function test_broken_and_encrypted_documents_fail_closed(): void
    {
        $broken = $this->directory.'/broken.pdf';
        file_put_contents($broken, "%PDF-1.4\nrubbish\n%%EOF\n");
        $this->assertSame('rejected', app(PdfDocumentScanner::class)->scan($broken)['status']);
        $encrypted = $this->directory.'/encrypted.pdf';
        $process = new Process([config('registration-documents.qpdf'), '--encrypt', 'test-user', 'test-owner', '256', '--', $this->pdf(), $encrypted]);
        $this->assertSame(0, $process->run(), $process->getErrorOutput());
        $this->assertSame('rejected', app(PdfDocumentScanner::class)->scan($encrypted)['status']);
    }

    public function test_real_clamav_detects_the_harmless_test_signature(): void
    {
        $result = app(PdfDocumentScanner::class)->scan($this->pdf('', 'TFSF-SCANNER-TEST-DETECTION'));
        $this->assertSame('rejected', $result['status']);
        $this->assertSame('malware_detected', $result['reason']);
    }

    public function test_unavailable_daemon_cannot_produce_a_clean_result(): void
    {
        $config = $this->directory.'/unavailable.conf';
        file_put_contents($config, "LocalSocket /tmp/tfsf-nonexistent-clamd.sock\n");
        config(['registration-documents.clamd_config' => $config]);
        $this->assertSame('error', app(PdfDocumentScanner::class)->scan($this->pdf())['status']);
    }

    public function test_excessive_tool_output_is_bounded(): void
    {
        config(['registration-documents.max_json_bytes' => 16]);
        $this->expectException(\RuntimeException::class);
        app(PdfDocumentScanner::class)->scan($this->pdf());
    }

    public function test_stalled_parser_has_a_deadline(): void
    {
        $tool = $this->directory.'/stalled-parser';
        file_put_contents($tool, "#!/bin/sh\nsleep 2\n");
        chmod($tool, 0700);
        config(['registration-documents.qpdf' => $tool, 'registration-documents.process_timeout' => 0.1]);
        $this->expectException(ProcessTimedOutException::class);
        app(PdfDocumentScanner::class)->scan($this->pdf());
    }
}
