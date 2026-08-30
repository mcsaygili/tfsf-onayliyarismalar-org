<?php

namespace App\Support\Identity;

final class TurkishIdentityNumber
{
    public static function isValid(?string $value): bool
    {
        if (! is_string($value) || ! preg_match('/^[1-9][0-9]{10}$/', $value)) {
            return false;
        }

        $digits = array_map('intval', str_split($value));
        $odd = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $even = $digits[1] + $digits[3] + $digits[5] + $digits[7];

        return (($odd * 7 - $even) % 10 + 10) % 10 === $digits[9]
            && array_sum(array_slice($digits, 0, 10)) % 10 === $digits[10];
    }
}
