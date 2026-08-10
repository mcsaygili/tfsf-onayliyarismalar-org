<?php

namespace App\Support\Photo;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * `photos` tablosunun EXIF sütunlarını dolduran servis. PHP'nin `exif`/`gd`
 * eklentileri bu sunucuda YÜKLÜ DEĞİL (bkz. proje planı) — bu yüzden
 * `exif_read_data()` yerine, container'da zaten kurulu olan `exiftool` CLI
 * ikilisi (`libimage-exiftool-perl`) shell'den çağrılıyor.
 *
 * `exiftool` bulunamazsa, çıktısı bozuksa, ya da dosya gerçek bir görsel
 * değilse: yükleme ASLA başarısız olmamalı — sadece `exif_missing = true`
 * olarak işaretlenir (bkz. istek maddesi 4).
 */
class ExifReader
{
    /** @var array<int, string> */
    private const REQUESTED_TAGS = [
        'Make', 'Model', 'LensModel', 'FocalLength', 'FNumber',
        'ExposureTime', 'ISO', 'DateTimeOriginal', 'ColorSpace',
        'ImageWidth', 'ImageHeight',
    ];

    /** @var array<int, string> — bunlardan en az biri yoksa exif_missing=true (genişlik/yükseklik tek başına EXIF sayılmaz) */
    private const CAMERA_TAGS = [
        'Make', 'Model', 'LensModel', 'FocalLength', 'FNumber',
        'ExposureTime', 'ISO', 'DateTimeOriginal',
    ];

    /**
     * @return array<string, mixed>
     */
    public function read(string $absolutePath): array
    {
        try {
            $data = $this->runExiftool($absolutePath);
        } catch (\Throwable $e) {
            Log::warning('EXIF okunamadı', ['path' => $absolutePath, 'error' => $e->getMessage()]);
            $data = [];
        }

        $hasCameraData = collect(self::CAMERA_TAGS)->contains(fn ($tag) => filled($data[$tag] ?? null));

        return [
            'camera_make' => $data['Make'] ?? null,
            'camera_model' => $data['Model'] ?? null,
            'lens' => $data['LensModel'] ?? null,
            'focal_length' => $data['FocalLength'] ?? null,
            'aperture' => $this->formatAperture($data['FNumber'] ?? null),
            'shutter_speed' => isset($data['ExposureTime']) ? (string) $data['ExposureTime'] : null,
            'iso' => isset($data['ISO']) ? (int) $data['ISO'] : null,
            'exif_captured_at' => $this->parseExifDate($data['DateTimeOriginal'] ?? null),
            'color_space' => $data['ColorSpace'] ?? null,
            'width' => isset($data['ImageWidth']) ? (int) $data['ImageWidth'] : null,
            'height' => isset($data['ImageHeight']) ? (int) $data['ImageHeight'] : null,
            'exif_raw' => $data ?: null,
            'exif_missing' => ! $hasCameraData,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runExiftool(string $absolutePath): array
    {
        $command = array_merge(
            ['exiftool', '-json'],
            array_map(fn ($tag) => "-{$tag}", self::REQUESTED_TAGS),
            [$absolutePath]
        );

        $process = new Process($command);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (ProcessExceptionInterface $e) {
            throw new \RuntimeException('exiftool çalıştırılamadı: '.$e->getMessage(), previous: $e);
        }

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('exiftool başarısız çıktı verdi: '.$process->getErrorOutput());
        }

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded) || ! isset($decoded[0]) || ! is_array($decoded[0])) {
            throw new \RuntimeException('exiftool çıktısı beklenmeyen formatta.');
        }

        unset($decoded[0]['SourceFile']);

        return $decoded[0];
    }

    private function formatAperture(mixed $fNumber): ?string
    {
        if ($fNumber === null || $fNumber === '') {
            return null;
        }

        $value = (string) $fNumber;

        return str_starts_with($value, 'f/') ? $value : "f/{$value}";
    }

    private function parseExifDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y:m:d H:i:s', (string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
