<?php

namespace Tests\Support;

class PdfScanFixtures
{
    public static function bytes(string $catalog = '', string $marker = 'Synthetic document'): string
    {
        $bytes = "%PDF-1.4\n";
        $objects = ['<< /Type /Catalog /Pages 2 0 R '.$catalog.' >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Contents 4 0 R >>'];
        $stream = '% '.$marker."\n";
        $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($bytes);
            $bytes .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($bytes);
        $bytes .= "xref\n0 5\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $bytes .= sprintf('%010d 00000 n ', $offset)."\n";
        }

        return $bytes."trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
