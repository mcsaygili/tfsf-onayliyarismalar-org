<?php

namespace App\Support\CompetitionWizard;

use App\Enums\CompetitionInfrastructureProvider;
use App\Models\Competition;
use App\Models\CompetitionType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

/** Adım 4 — TFSF altyapısında kullanılacak yarışma modelinin seçimi. */
class Step4 implements CompetitionStep
{
    public function number(): int
    {
        return 4;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.4.label');
    }

    public function isImplemented(): bool
    {
        return true;
    }

    public function isApplicable(Competition $competition): bool
    {
        return $competition->infrastructure_provider === CompetitionInfrastructureProvider::Tfsf;
    }

    public function data(Competition $competition): array
    {
        return ['competition_type' => $competition->competition_type_id];
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (array_key_exists('competition_type', $validated)) {
            $attributes = ['competition_type_id' => $validated['competition_type']];
            $type = CompetitionType::query()->find($validated['competition_type']);

            if ($type?->code !== Step5::PHOTOGRAPHERS_MARATHON_CODE) {
                $attributes += [
                    'country_id' => null,
                    'city_id' => null,
                    'participant_approval_process_id' => null,
                ];
            }

            $competition->update($attributes);
        }
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        return [
            'competition_type' => [
                $isDraftSave ? 'nullable' : 'required',
                'uuid',
                Rule::exists('competition_types', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /** @return Collection<int, CompetitionType> */
    public function options(): Collection
    {
        return CompetitionType::active()->ordered()->with('translations')->get();
    }
}
