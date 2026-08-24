<?php

namespace App\Support\CompetitionRules;

use App\Models\CompetitionCategory;
use Carbon\CarbonImmutable;

/**
 * Step 6'da tanımlanan kuralları katılım sırasında tek ve deterministik
 * noktadan değerlendirir. İstemci arayüzü bu sonucu yalnızca gösterir;
 * nihai karar daima sunucu tarafındadır.
 */
class CompetitionEligibilityEvaluator
{
    /**
     * @param  array{birth_date?: string, gender_id?: string, member_group_ids?: array<int, string>, capture_device_id?: string, processing_method_ids?: array<int, string>}  $participant
     * @return array{eligible: bool, violations: array<int, string>}
     */
    public function evaluate(CompetitionCategory $category, array $participant): array
    {
        $category->loadMissing([
            'competition', 'genders', 'ageEligibilityRule', 'memberGroups',
            'captureDevices', 'processingMethods',
        ]);

        $violations = [];
        $this->evaluateGender($category, $participant, $violations);
        $this->evaluateAge($category, $participant, $violations);
        $this->evaluateMembership($category, $participant, $violations);
        $this->evaluateDevice($category, $participant, $violations);
        $this->evaluateProcessing($category, $participant, $violations);

        return ['eligible' => $violations === [], 'violations' => $violations];
    }

    /** @param array<int, string> $violations */
    private function evaluateGender(CompetitionCategory $category, array $participant, array &$violations): void
    {
        $gender = $category->genders->first();
        if (! $gender || $gender->code === 'no-check') {
            return;
        }

        if (($participant['gender_id'] ?? null) !== $gender->id) {
            $violations[] = 'gender_not_eligible';
        }
    }

    /** @param array<int, string> $violations */
    private function evaluateAge(CompetitionCategory $category, array $participant, array &$violations): void
    {
        $rule = $category->ageEligibilityRule;
        if (! $rule || $rule->code === 'no-age-check') {
            return;
        }

        if (blank($participant['birth_date'] ?? null) || ! $category->competition?->competition_ends_at) {
            $violations[] = 'birth_date_required';

            return;
        }

        $age = (int) floor(CarbonImmutable::parse($participant['birth_date'])
            ->diffInYears($category->competition->competition_ends_at));

        $minimumPasses = $rule->minimum_age === null
            || ($rule->minimum_inclusive ? $age >= $rule->minimum_age : $age > $rule->minimum_age);
        $maximumPasses = $rule->maximum_age === null
            || ($rule->maximum_inclusive ? $age <= $rule->maximum_age : $age < $rule->maximum_age);
        $eligible = $minimumPasses && $maximumPasses;

        if (! $eligible) {
            $violations[] = 'age_not_eligible';
        }
    }

    /** @param array<int, string> $violations */
    private function evaluateMembership(CompetitionCategory $category, array $participant, array &$violations): void
    {
        $allowed = $category->memberGroups->pluck('id');
        if ($category->memberGroups->contains('code', 'no-membership-check')) {
            return;
        }

        $actual = collect($participant['member_group_ids'] ?? []);
        $eligible = $category->member_group_match_mode === 'all'
            ? $allowed->diff($actual)->isEmpty()
            : $allowed->intersect($actual)->isNotEmpty();

        if (! $eligible) {
            $violations[] = 'membership_not_eligible';
        }
    }

    /** @param array<int, string> $violations */
    private function evaluateDevice(CompetitionCategory $category, array $participant, array &$violations): void
    {
        if ($category->captureDevices->contains('code', 'no-device-check')) {
            return;
        }

        if (! $category->captureDevices->contains('id', $participant['capture_device_id'] ?? null)) {
            $violations[] = 'device_not_eligible';
        }
    }

    /** @param array<int, string> $violations */
    private function evaluateProcessing(CompetitionCategory $category, array $participant, array &$violations): void
    {
        if ($category->processingMethods->contains('code', 'no-processing-check')) {
            return;
        }

        $allowed = $category->processingMethods->pluck('id');
        if (collect($participant['processing_method_ids'] ?? [])->diff($allowed)->isNotEmpty()) {
            $violations[] = 'processing_method_not_eligible';
        }
    }
}
