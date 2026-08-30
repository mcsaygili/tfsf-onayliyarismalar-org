<?php

namespace App\Support\CompetitionIcons;

final class CompetitionIconRegistry
{
    public const DEFAULT = 'competition-standard';

    /** @return array<string, string> Semantic key => existing application icon name. */
    public static function options(): array
    {
        return [
            'competition-standard' => 'camera',
            'competition-marathon' => 'calendar',
            'competition-cup' => 'competitions',
            'competition-biennial' => 'staff',
            'competition-series' => 'layers',
            'competition-exhibition' => 'grid',
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function normalize(?string $key): string
    {
        return array_key_exists((string) $key, self::options()) ? (string) $key : self::DEFAULT;
    }

    public static function componentName(?string $key): string
    {
        return self::options()[self::normalize($key)];
    }
}
