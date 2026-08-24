<?php

namespace App\Rules;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;

/** Gün/ay/yıl/saat/dakika bileşenlerinden üretilen yerel tarihi katı biçimde doğrular. */
class ValidLocalDateTime implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $value, $parts)
            || (int) $parts[1] < 2020
            || (int) $parts[1] > 2040
            || (int) $parts[4] > 23
            || (int) $parts[5] > 59) {
            $fail(__('institution.competitions.validation.invalid_datetime'));

            return;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            $value,
            new DateTimeZone(config('app.timezone')),
        );
        $errors = DateTimeImmutable::getLastErrors();

        if (! $date
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d\TH:i') !== $value) {
            $fail(__('institution.competitions.validation.invalid_datetime'));
        }
    }
}
