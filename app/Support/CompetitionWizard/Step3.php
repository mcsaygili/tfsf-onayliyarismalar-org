<?php

namespace App\Support\CompetitionWizard;

use App\Enums\CompetitionInfrastructureProvider;
use App\Models\Competition;
use Illuminate\Validation\Rule;

/**
 * Adım 3 — Yarışma Alt Yapısı: yarışmanın teknik ve operasyonel
 * süreçlerinin TFSF sistemiyle mi, harici bir altyapıyla mı yürütüleceği.
 */
class Step3 implements CompetitionStep
{
    public function number(): int
    {
        return 3;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.3.label');
    }

    public function isImplemented(): bool
    {
        return true;
    }

    public function isApplicable(Competition $competition): bool
    {
        return true;
    }

    public function data(Competition $competition): array
    {
        return ['infrastructure_provider' => $competition->infrastructure_provider?->value];
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (array_key_exists('infrastructure_provider', $validated)) {
            $attributes = ['infrastructure_provider' => $validated['infrastructure_provider']];

            if ($validated['infrastructure_provider'] === CompetitionInfrastructureProvider::External->value) {
                $attributes += [
                    'competition_type_id' => null,
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
            'infrastructure_provider' => [
                $isDraftSave ? 'nullable' : 'required',
                Rule::enum(CompetitionInfrastructureProvider::class),
            ],
        ];
    }
}
