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
        $steps = [1 => new Step1];

        for ($i = 2; $i <= self::TOTAL_STEPS; $i++) {
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
            if ($step->isImplemented() && ! self::stepIsComplete($competition, $step)) {
                return false;
            }
        }

        return true;
    }

    private static function stepIsComplete(Competition $competition, CompetitionStep $step): bool
    {
        return Validator::make(
            $competition->only($step->fillable()),
            $step->rules(isDraftSave: false)
        )->passes();
    }
}
