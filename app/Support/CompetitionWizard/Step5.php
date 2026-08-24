<?php

namespace App\Support\CompetitionWizard;

use App\Models\City;
use App\Models\CityTranslation;
use App\Models\Competition;
use App\Models\CompetitionCaptureRegion;
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

        return (bool) ($competition->competitionType?->requires_location || $competition->competitionType?->requires_approval_process);
    }

    public function data(Competition $competition): array
    {
        $competition->loadMissing('captureRegions');

        $regions = $competition->captureRegions->map(fn (CompetitionCaptureRegion $region) => [
            'id' => $region->id,
            'country' => $region->country_id,
            'city' => $region->city_id,
        ])->values()->all();

        if ($regions === [] && $competition->country_id && $competition->city_id) {
            $regions[] = ['id' => null, 'country' => $competition->country_id, 'city' => $competition->city_id];
        }

        return [
            'regions' => $regions,
            'participant_approval_process' => $competition->participant_approval_process_id,
        ];
    }

    public function persist(Competition $competition, array $validated): void
    {
        $regions = $validated['regions'] ?? [];
        $keptIds = [];

        foreach ($regions as $index => $payload) {
            $region = isset($payload['id'])
                ? $competition->captureRegions()->whereKey($payload['id'])->firstOrFail()
                : new CompetitionCaptureRegion(['competition_id' => $competition->id]);
            $region->fill([
                'country_id' => $payload['country'],
                'city_id' => $payload['city'],
                'sort_order' => ($index + 1) * 10,
            ])->save();
            $keptIds[] = $region->id;
        }

        $competition->captureRegions()->whereNotIn('id', $keptIds)->delete();
        $firstRegion = $regions[0] ?? null;

        $competition->update([
            'country_id' => $firstRegion['country'] ?? null,
            'city_id' => $firstRegion['city'] ?? null,
            'participant_approval_process_id' => $validated['participant_approval_process'] ?? null,
        ]);
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $required = $isDraftSave ? 'nullable' : 'required';
        $requiresLocation = (bool) $competition->competitionType?->requires_location;
        $requiresApproval = (bool) $competition->competitionType?->requires_approval_process;

        return [
            'regions' => [$requiresLocation ? $required : 'nullable', 'array', $isDraftSave ? 'max:20' : 'min:1', 'max:20'],
            'regions.*.id' => ['nullable', 'uuid', Rule::exists('competition_capture_regions', 'id')->where('competition_id', $competition->id)],
            'regions.*.country' => [
                $requiresLocation ? $required : 'nullable',
                'uuid',
                Rule::exists('countries', 'id')->where('status', true)->whereNull('deleted_at'),
            ],
            'regions.*.city' => [
                $requiresLocation ? $required : 'nullable',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail) use ($competition) {
                    $index = explode('.', $attribute)[1] ?? null;
                    $countryId = request()->input(
                        "regions.$index.country",
                        data_get($this->data($competition), "regions.$index.country")
                    );

                    if (! City::query()->active()->whereKey($value)->where('country_id', $countryId)->exists()) {
                        $fail(__('institution.competitions.validation.city_country_mismatch'));
                    }
                },
            ],
            'participant_approval_process' => [
                $requiresApproval ? $required : 'nullable',
                'uuid',
                Rule::exists('participant_approval_processes', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /** @return array<int, array{id: string|null, country: string, city: string}> */
    public function formRegions(Competition $competition): array
    {
        return old('regions', $this->data($competition)['regions']) ?: [
            ['id' => null, 'country' => '', 'city' => ''],
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
