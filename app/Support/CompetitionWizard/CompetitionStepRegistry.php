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
            5 => new Step5,
            6 => new Step6,
            7 => new Step7,
            8 => new Step8,
            9 => new PlaceholderStep(9),
            10 => new Step10,
        ];

        return $steps;
    }

    public static function get(int $step): CompetitionStep
    {
        return self::all()[$step] ?? abort(404);
    }

    public static function canSubmit(Competition $competition): bool
    {
        foreach (self::all() as $step) {
            if (! $step->isApplicable($competition)) {
                continue;
            }

            if (! $step->isImplemented() || ! self::stepIsComplete($competition, $step)) {
                return false;
            }
        }

        return true;
    }

    public static function nextApplicableStepNumber(int $step, Competition $competition): int
    {
        for ($number = $step + 1; $number <= self::TOTAL_STEPS; $number++) {
            $candidate = self::get($number);

            if ($candidate->isImplemented() && $candidate->isApplicable($competition)) {
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

    public static function firstBlockingStepNumber(Competition $competition): ?int
    {
        foreach (self::all() as $number => $step) {
            if (! $step->isApplicable($competition)) {
                continue;
            }

            if (! $step->isImplemented() || ! self::stepIsComplete($competition, $step)) {
                return $number;
            }
        }

        return null;
    }

    /** @return 'complete'|'incomplete'|'not_applicable'|'locked'|'current' */
    public static function stateFor(Competition $competition, CompetitionStep $step, int $viewedStep): string
    {
        if (! $step->isApplicable($competition)) {
            return 'not_applicable';
        }

        if ($step->number() > $competition->current_step) {
            return 'locked';
        }

        if ($step->number() === $viewedStep) {
            return 'current';
        }

        return $step->isImplemented() && self::stepIsComplete($competition, $step)
            ? 'complete'
            : 'incomplete';
    }

    public static function stepIsComplete(Competition $competition, CompetitionStep $step): bool
    {
        return Validator::make(
            $step->data($competition),
            $step->rules(isDraftSave: false, competition: $competition)
        )->passes();
    }
}
