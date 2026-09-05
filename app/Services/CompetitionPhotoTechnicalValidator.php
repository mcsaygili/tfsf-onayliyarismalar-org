<?php

namespace App\Services;

use App\Models\CompetitionCategory;
use App\Support\Photo\CategoryPhotoRules;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class CompetitionPhotoTechnicalValidator
{
    public function validate(CompetitionCategory $category, string $bytes): array
    {
        $rules = CategoryPhotoRules::normalize($category->photo_rules);
        $size = strlen($bytes);
        $maxMb = min($rules['max_file_size_mb'] ?? INF, config('competition-photos.max_file_size_mb'));
        if ($size > $maxMb * 1048576) {
            $this->fail('max_file_size_mb', $maxMb);
        }
        if ($rules['min_file_size_mb'] && $size < $rules['min_file_size_mb'] * 1048576) {
            $this->fail('min_file_size_mb', $rules['min_file_size_mb']);
        }

        $image = @getimagesizefromstring($bytes);
        $format = match ($image[2] ?? null) {
            IMAGETYPE_JPEG => 'jpeg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', default => null,
        };
        if (! $format || ! in_array($format, $rules['formats'], true)) {
            $this->fail('formats', implode(', ', array_map('strtoupper', $rules['formats'])));
        }
        [$width, $height] = $image;
        if ($width < 1 || $height < 1 || $width * $height > config('competition-photos.max_pixels')) {
            $this->fail('max_pixels', config('competition-photos.max_pixels'));
        }
        if ($rules['min_short_edge'] && min($width, $height) < $rules['min_short_edge']) {
            $this->fail('min_short_edge', $rules['min_short_edge']);
        }
        if ($rules['max_long_edge'] && max($width, $height) > $rules['max_long_edge']) {
            $this->fail('max_long_edge', $rules['max_long_edge']);
        }

        $dpi = null;
        if ($rules['min_dpi'] || $rules['max_dpi']) {
            $dpi = $this->resolution($bytes);
            if ($dpi === null) {
                $this->fail('dpi_missing');
            }
            if ($rules['min_dpi'] && min($dpi['x'], $dpi['y']) < $rules['min_dpi']) {
                $this->fail('min_dpi', $rules['min_dpi']);
            }
            if ($rules['max_dpi'] && max($dpi['x'], $dpi['y']) > $rules['max_dpi']) {
                $this->fail('max_dpi', $rules['max_dpi']);
            }
        }

        return ['version' => 1, 'rules' => $rules, 'file_size_bytes' => $size, 'mime_type' => 'image/'.$format,
            'width' => $width, 'height' => $height, 'dpi' => $dpi];
    }

    private function resolution(string $bytes): ?array
    {
        // Read only original-image resolution groups. IFD1 thumbnail and XMP
        // descriptions must not impersonate the primary image's physical units.
        $process = new Process(['exiftool', '-j', '-n', '-G1', '-s', '-a', '-XResolution', '-YResolution',
            '-ResolutionUnit', '-PixelsPerUnitX', '-PixelsPerUnitY', '-PixelUnits', '-']);
        $process->setInput($bytes)->setTimeout(5);
        try {
            $process->mustRun();
            $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)[0] ?? [];
        } catch (\Throwable) {
            $this->fail('dpi_unreadable');
        }

        // EXIF primary-image tags take precedence over native fallback tags.
        foreach (['IFD0', 'JFIF', 'PNG-pHYs'] as $group) {
            $xKey = $group === 'PNG-pHYs' ? 'PixelsPerUnitX' : 'XResolution';
            $yKey = $group === 'PNG-pHYs' ? 'PixelsPerUnitY' : 'YResolution';
            $unitKey = $group === 'PNG-pHYs' ? 'PixelUnits' : 'ResolutionUnit';
            if (! array_key_exists("$group:$xKey", $data) && ! array_key_exists("$group:$yKey", $data)) {
                continue;
            }
            $unit = $data["$group:$unitKey"] ?? null;
            $factor = match ($group) {
                'IFD0' => match ($unit) {
                    2 => 1, 3 => 2.54, default => null
                },
                'JFIF' => match ($unit) {
                    1 => 1, 2 => 2.54, default => null
                },
                'PNG-pHYs' => $unit === 1 ? 0.0254 : null,
            };
            $x = $data["$group:$xKey"] ?? null;
            $y = $data["$group:$yKey"] ?? null;
            if ($factor === null || ! is_numeric($x) || ! is_numeric($y) || $x <= 0 || $y <= 0) {
                return null;
            }

            // PNG stores whole pixels/metre; normalize its sub-centi-DPI rounding.
            return ['x' => round($x * $factor, 2), 'y' => round($y * $factor, 2), 'source' => $group];
        }

        return null;
    }

    private function fail(string $rule, mixed $value = null): never
    {
        throw ValidationException::withMessages(['photo' => __('photo_rules.errors.'.$rule, ['value' => $value])]);
    }
}
