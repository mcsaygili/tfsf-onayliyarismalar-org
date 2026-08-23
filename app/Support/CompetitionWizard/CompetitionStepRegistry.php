<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;
use Illuminate\Support\Facades\Validator;

/**
 * Sihirbazın 10 adımının tek merkezi listesi. Yeni bir adım eklemek =
 * burada ilgili PlaceholderStep'i gerçek bir CompetitionStep sınıfıyla
 * değiştirmek + bir view yazmak (bkz. proje planı).
 */
class CompetitionStepRegistry
{
    public const TOTAL_STEPS = 10;

    /**
     * @return array<int, CompetitionStep>
     */
    public static function all(): array
    {
        $steps = [
            1 => new Step1,
            2 => new Step2,
            3 => new Step3,
            4 => new Step4,
        ];

        for ($i = 5; $i <= self::TOTAL_STEPS; $i++) {
            $steps[$i] = new PlaceholderStep($i);
        }

        return $steps;
    }

    public static function get(int $step): CompetitionStep
    {
        return self::all()[$step] ?? abort(404);
    }

    public static function canSubmit(Competition $competition): bool
    {
        foreach (self::all() as $step) {
            if ($step->isApplicable($competition) && $step->isImplemented() && ! self::stepIsComplete($competition, $step)) {
                return false;
            }
        }

        return true;
    }

    public static function nextApplicableStepNumber(int $step, Competition $competition): int
    {
        for ($number = $step + 1; $number <= self::TOTAL_STEPS; $number++) {
            if (self::get($number)->isApplicable($competition)) {
                return $number;
            }
        }

        return self::TOTAL_STEPS;
    }

    public static function firstIncompleteStepNumber(Competition $competition): ?int
    {
        foreach (self::all() as $number => $step) {
            if ($step->isApplicable($competition) && $step->isImplemented() && ! self::stepIsComplete($competition, $step)) {
                return $number;
            }
        }

        return null;
    }

    private static function stepIsComplete(Competition $competition, CompetitionStep $step): bool
    {
        return Validator::make(
            $step->data($competition),
            $step->rules(isDraftSave: false, competition: $competition)
        )->passes();
    }
}
