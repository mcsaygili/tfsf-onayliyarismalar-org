<?php

namespace App\Support\CompetitionWizard;

use App\Models\City;
use App\Models\CityTranslation;
use App\Models\Competition;
use App\Models\Country;
use App\Models\CountryTranslation;
use App\Models\ParticipantApprovalProcess;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

/** Adım 5 — Yarışma türüne özgü operasyon bilgileri. */
class Step5 implements CompetitionStep
{
    public const PHOTOGRAPHERS_MARATHON_CODE = 'photographers-marathon';

    public function number(): int
    {
        return 5;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.5.label');
    }

    public function isImplemented(): bool
    {
        return true;
    }

    public function isApplicable(Competition $competition): bool
    {
        $competition->loadMissing('competitionType');

        return $competition->competitionType?->code === self::PHOTOGRAPHERS_MARATHON_CODE;
    }

    public function data(Competition $competition): array
    {
        return [
            'country' => $competition->country_id,
            'city' => $competition->city_id,
            'participant_approval_process' => $competition->participant_approval_process_id,
        ];
    }

    public function persist(Competition $competition, array $validated): void
    {
        $competition->update([
            'country_id' => $validated['country'] ?? null,
            'city_id' => $validated['city'] ?? null,
            'participant_approval_process_id' => $validated['participant_approval_process'] ?? null,
        ]);
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $required = $isDraftSave ? 'nullable' : 'required';
        $selectedCountry = request()->input('country');

        return [
            'country' => [
                $required,
                'uuid',
                Rule::exists('countries', 'id')->where('status', true)->whereNull('deleted_at'),
            ],
            'city' => [
                $required,
                'uuid',
                Rule::exists('cities', 'id')
                    ->where('country_id', $selectedCountry)
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'participant_approval_process' => [
                $required,
                'uuid',
                Rule::exists('participant_approval_processes', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /** @return Collection<int, Country> */
    public function countries(): Collection
    {
        $locale = app()->getLocale();

        return Country::active()
            ->with('translations')
            ->orderBy(
                CountryTranslation::select('official_name')
                    ->whereColumn('country_id', 'countries.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->get();
    }

    /** @return Collection<int, City> */
    public function cities(?string $countryId): Collection
    {
        if (! $countryId) {
            return new Collection;
        }

        $locale = app()->getLocale();

        return City::active()
            ->where('country_id', $countryId)
            ->with('translations')
            ->orderBy(
                CityTranslation::select('official_name')
                    ->whereColumn('city_id', 'cities.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->get();
    }

    /** @return Collection<int, ParticipantApprovalProcess> */
    public function approvalProcesses(): Collection
    {
        return ParticipantApprovalProcess::active()->ordered()->with('translations')->get();
    }
}
