<?php

namespace App\Support\CompetitionRegulations;

use App\Models\Competition;
use App\Support\Photo\CategoryPhotoRules;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Database\Eloquent\Model;

class CompetitionRegulationContextBuilder
{
    /** @return array<string, mixed> */
    public function build(Competition $competition, string $locale): array
    {
        $competition->loadMissing([
            'translations', 'institution', 'competitionType.translations',
            'participantApprovalProcess.translations',
            'captureRegions.country.translations', 'captureRegions.city.translations',
            'categories.translations', 'categories.genders.translations',
            'categories.ageEligibilityRule.translations', 'categories.memberGroups.translations',
            'categories.captureDevices.translations', 'categories.processingMethods.translations',
            'categories.awards.translations', 'categories.awards.awardReference.translations',
            'categories.jurorAssignments.juror', 'categories.jurorAssignments.invitation',
            'categories.evaluationCriteria.criterion.translations',
            'regulationInputs',
        ]);

        $translation = $competition->getTranslation($locale, false);

        return [
            'competition' => [
                'name' => $translation?->name,
                'subject' => $translation?->subject,
                'purpose' => $translation?->purpose,
                'partners' => $competition->partners,
                'audience' => $competition->audience?->value,
                'audience_label' => $this->audienceLabel($competition->audience?->value, $locale),
                'infrastructure_provider' => $competition->infrastructure_provider?->value,
                'infrastructure_label' => $this->infrastructureLabel($competition->infrastructure_provider?->value, $locale),
                'type_code' => $competition->competitionType?->code,
                'type_name' => $this->translated($competition->competitionType, 'name', $locale),
                'approval_process_code' => $competition->participantApprovalProcess?->code,
                'approval_process_name' => $this->translated($competition->participantApprovalProcess, 'name', $locale),
                'application_starts_at' => $this->date($competition->application_starts_at, $locale),
                'application_ends_at' => $this->date($competition->application_ends_at, $locale),
                'competition_ends_at' => $this->date($competition->competition_ends_at, $locale),
            ],
            'institution' => ['name' => $competition->institution?->name],
            'categories' => $competition->categories->map(fn ($category) => [
                'name' => $this->translated($category, 'name', $locale),
                'genders' => $this->translatedList($category->genders, 'name', $locale),
                'age_rule' => $this->translated($category->ageEligibilityRule, 'name', $locale),
                'declarations' => SubmissionDeclarations::summary($category, $locale),
                'photo_rules' => CategoryPhotoRules::summary($category->photo_rules, $locale),
                'age_rule_code' => $category->ageEligibilityRule?->code,
                'member_groups' => $this->translatedList($category->memberGroups, 'name', $locale),
                'capture_devices' => $this->translatedList($category->captureDevices, 'name', $locale),
                'processing_methods' => $this->translatedList($category->processingMethods, 'name', $locale),
            ])->values()->all(),
            'awards' => $competition->categories->flatMap(function ($category) use ($locale) {
                return $category->awards->map(fn ($award) => [
                    'category_name' => $this->translated($category, 'name', $locale),
                    'name' => $this->translated($award->awardReference, 'name', $locale),
                    'quantity' => $award->quantity,
                    'special_award_text' => $this->translated($award, 'special_award_text', $locale),
                    'material_award' => $this->translated($award, 'material_award', $locale),
                ]);
            })->values()->all(),
            'capture_regions' => $competition->captureRegions->map(function ($region) use ($locale) {
                $city = $this->translated($region->city, 'official_name', $locale) ?? $region->city?->official_name;
                $country = $this->translated($region->country, 'official_name', $locale);

                return ['city' => $city, 'country' => $country, 'name' => collect([$city, $country])->filter()->join(', ')];
            })->values()->all(),
            'jurors' => $competition->categories->flatMap(function ($category) use ($locale) {
                return $category->jurorAssignments->map(function ($assignment) use ($category, $locale) {
                    $name = $assignment->juror
                        ? trim($assignment->juror->first_name.' '.$assignment->juror->last_name)
                        : trim(($assignment->invitation?->first_name ?? '').' '.($assignment->invitation?->last_name ?? ''));
                    $registered = $assignment->juror !== null;

                    return [
                        'category_name' => $this->translated($category, 'name', $locale),
                        'name' => $name,
                        'status' => $registered ? 'registered' : 'invited',
                        'status_label' => $locale === 'en'
                            ? ($registered ? 'Registered' : 'Invitation pending')
                            : ($registered ? 'Kayıtlı' : 'Davet bekliyor'),
                    ];
                });
            })->values()->all(),
            'criteria' => $competition->categories->flatMap(function ($category) use ($locale) {
                return $category->evaluationCriteria->map(fn ($assignment) => [
                    'category_name' => $this->translated($category, 'name', $locale),
                    'name' => $this->translated($assignment->criterion, 'name', $locale),
                    'description' => $this->translated($assignment->criterion, 'description', $locale),
                    'min_score' => $assignment->min_score,
                    'max_score' => $assignment->max_score,
                    'score_range' => $assignment->min_score.'–'.$assignment->max_score,
                    'weight' => rtrim(rtrim($assignment->weight, '0'), '.'),
                ]);
            })->values()->all(),
        ];
    }

    private function translated(?Model $model, string $attribute, string $locale): mixed
    {
        if (! $model || ! method_exists($model, 'getTranslation')) {
            return $model?->{$attribute};
        }

        return $model->getTranslation($locale, false)?->{$attribute};
    }

    private function translatedList(iterable $models, string $attribute, string $locale): array
    {
        return collect($models)->map(fn ($model) => $this->translated($model, $attribute, $locale))->filter()->values()->all();
    }

    private function date($value, string $locale): ?string
    {
        return $value?->format($locale === 'en' ? 'd/m/Y H:i' : 'd.m.Y H:i');
    }

    private function audienceLabel(?string $value, string $locale): ?string
    {
        return match ([$value, $locale]) {
            ['national', 'en'] => 'National Competition',
            ['international', 'en'] => 'International Competition',
            ['national', 'tr'] => 'Ulusal Yarışma',
            ['international', 'tr'] => 'Uluslararası Yarışma',
            default => null,
        };
    }

    private function infrastructureLabel(?string $value, string $locale): ?string
    {
        return match ([$value, $locale]) {
            ['tfsf', 'en'] => 'Using TFSF Infrastructure',
            ['external', 'en'] => 'Without TFSF Infrastructure',
            ['tfsf', 'tr'] => 'TFSF Alt Yapısı Kullanılarak',
            ['external', 'tr'] => 'TFSF Alt Yapısı Kullanılmadan',
            default => null,
        };
    }
}
