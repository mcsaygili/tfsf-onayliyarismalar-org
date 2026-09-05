<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Services\CompetitionPhotoTechnicalValidator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CompetitionPhotoTechnicalRulesTest extends TestCase
{
    private function bytes(string $format = 'jpeg', int $width = 80, int $height = 60, array $tags = []): string
    {
        $image = new \Imagick;
        $image->newImage($width, $height, 'gray');
        $image->setImageFormat($format);
        $bytes = $image->getImageBlob();
        $image->clear();
        if ($tags === []) {
            return $bytes;
        }
        $path = tempnam(sys_get_temp_dir(), 'tfsf-photo-rule-');
        try {
            file_put_contents($path, $bytes);
            (new Process(['exiftool', '-overwrite_original', ...$tags, $path]))->mustRun();

            return file_get_contents($path);
        } finally {
            unlink($path);
        }
    }

    private function validateBytes(string $bytes, array $rules = []): array
    {
        return app(CompetitionPhotoTechnicalValidator::class)->validate(new CompetitionCategory(['photo_rules' => $rules]), $bytes);
    }

    public static function rejectedLimits(): array
    {
        return [
            'short edge lower bound' => [['min_short_edge' => 61], 'min_short_edge'],
            'long edge upper bound' => [['max_long_edge' => 79], 'max_long_edge'],
            'file lower bound' => [['min_file_size_mb' => 1], 'min_file_size_mb'],
            'file upper bound' => [['max_file_size_mb' => 0.00001], 'max_file_size_mb'],
            'actual format' => [['formats' => ['png']], 'formats'],
            'missing physical resolution' => [['min_dpi' => 72], 'dpi_missing'],
        ];
    }

    #[DataProvider('rejectedLimits')]
    public function test_each_limit_is_independently_enforced(array $rules, string $error): void
    {
        try {
            $this->validateBytes($this->bytes(), $rules);
            $this->fail('The photo should have been rejected.');
        } catch (ValidationException $exception) {
            $value = $rules[$error] ?? null;
            $this->assertSame([__('photo_rules.errors.'.$error, ['value' => is_array($value) ? 'PNG' : $value])], $exception->errors()['photo']);
        }
    }

    public function test_exact_size_edges_and_dpi_boundaries_are_inclusive(): void
    {
        $bytes = $this->bytes(tags: ['-IFD0:XResolution=300', '-IFD0:YResolution=300', '-IFD0:ResolutionUnit#=2']);
        $result = $this->validateBytes($bytes, ['min_short_edge' => 60, 'max_long_edge' => 80,
            'min_dpi' => 300, 'max_dpi' => 300, 'min_file_size_mb' => strlen($bytes) / 1048576, 'max_file_size_mb' => strlen($bytes) / 1048576]);
        $this->assertSame(['x' => 300.0, 'y' => 300.0, 'source' => 'IFD0'], $result['dpi']);
        $this->assertSame(80, $result['width']);
    }

    public static function axisViolations(): array
    {
        return [[301, 300, 'max_dpi'], [300, 299, 'min_dpi'], [299, 300, 'min_dpi'], [300, 301, 'max_dpi']];
    }

    #[DataProvider('axisViolations')]
    public function test_both_axes_obey_both_dpi_bounds(int $x, int $y, string $error): void
    {
        $bytes = $this->bytes(tags: ["-IFD0:XResolution=$x", "-IFD0:YResolution=$y", '-IFD0:ResolutionUnit#=2']);
        try {
            $this->validateBytes($bytes, ['min_dpi' => 300, 'max_dpi' => 300]);
            $this->fail('Both axes must be checked.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('photo_rules.errors.'.$error, ['value' => 300])], $exception->errors()['photo']);
        }
    }

    public function test_centimetres_are_converted_and_exif_takes_precedence_over_jfif(): void
    {
        $bytes = $this->bytes(tags: ['-IFD0:XResolution=100', '-IFD0:YResolution=100', '-IFD0:ResolutionUnit#=3',
            '-JFIF:XResolution=72', '-JFIF:YResolution=72', '-JFIF:ResolutionUnit#=1']);
        $this->assertSame(254.0, $this->validateBytes($bytes, ['min_dpi' => 254, 'max_dpi' => 254])['dpi']['x']);
    }

    public function test_native_png_and_jfif_resolution_are_supported_without_exif(): void
    {
        $png = $this->bytes('png', tags: ['-PixelsPerUnitX=11811', '-PixelsPerUnitY=11811', '-PixelUnits#=1']);
        $jpeg = $this->bytes(tags: ['-JFIF:XResolution=300', '-JFIF:YResolution=300', '-JFIF:ResolutionUnit#=1']);
        $this->assertSame('PNG-pHYs', $this->validateBytes($png, ['min_dpi' => 300, 'max_dpi' => 300])['dpi']['source']);
        $this->assertSame('JFIF', $this->validateBytes($jpeg, ['min_dpi' => 300, 'max_dpi' => 300])['dpi']['source']);
    }

    public function test_unknown_exif_unit_cannot_fall_back_to_valid_jfif(): void
    {
        $bytes = $this->bytes(tags: ['-IFD0:XResolution=300', '-IFD0:YResolution=300', '-IFD0:ResolutionUnit#=1',
            '-JFIF:XResolution=300', '-JFIF:YResolution=300', '-JFIF:ResolutionUnit#=1']);
        $this->expectException(ValidationException::class);
        $this->validateBytes($bytes, ['min_dpi' => 300]);
    }

    public function test_defaults_allow_all_existing_formats_without_exif_and_portrait_edges(): void
    {
        foreach (['jpeg', 'png', 'webp'] as $format) {
            $result = $this->validateBytes($this->bytes($format, 60, 80), ['min_short_edge' => 60, 'max_long_edge' => 80]);
            $this->assertSame('image/'.$format, $result['mime_type']);
            $this->assertNull($result['dpi']);
        }
    }

    public function test_pixel_budget_is_checked_before_rendering(): void
    {
        config(['competition-photos.max_pixels' => 4799]);
        $this->expectException(ValidationException::class);
        $this->validateBytes($this->bytes());
    }

    public function test_global_file_limit_applies_even_without_category_limit(): void
    {
        config(['competition-photos.max_file_size_mb' => 1]);
        $this->expectException(ValidationException::class);
        $this->validateBytes($this->bytes().str_repeat('x', 1048576));
    }
}
