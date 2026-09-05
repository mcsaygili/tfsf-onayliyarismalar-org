<?php

namespace App\Support\Photo;

class CategoryPhotoRules
{
    public const FORMATS = ['jpeg', 'png', 'webp'];

    public const LIMITS = ['min_file_size_mb', 'max_file_size_mb', 'min_short_edge', 'max_long_edge', 'min_dpi', 'max_dpi'];

    public static function defaults(): array
    {
        return ['formats' => self::FORMATS, ...array_fill_keys(self::LIMITS, null)];
    }

    public static function normalize(?array $rules): array
    {
        $result = array_replace(self::defaults(), $rules ?? []);
        foreach (self::LIMITS as $key) {
            $result[$key] = ($result[$key] ?? 0) > 0 ? (float) $result[$key] : null;
        }

        return $result;
    }

    public static function summary(?array $rules, ?string $locale = null): string
    {
        $rules = self::normalize($rules);
        $parts = [__('photo_rules.accepted_formats', ['formats' => implode(', ', array_map('strtoupper', $rules['formats']))], $locale)];
        $rules['max_file_size_mb'] = min($rules['max_file_size_mb'] ?? INF, config('competition-photos.max_file_size_mb'));
        foreach (self::LIMITS as $key) {
            if ($rules[$key] !== null) {
                $parts[] = __('photo_rules.summary.'.$key, ['value' => $rules[$key]], $locale);
            }
        }
        if ($rules['min_dpi'] || $rules['max_dpi']) {
            $parts[] = __('photo_rules.dpi_required', [], $locale);
        }

        return implode(' · ', $parts);
    }
}
