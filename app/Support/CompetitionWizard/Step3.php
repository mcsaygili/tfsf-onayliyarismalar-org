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
        return [
            'infrastructure_provider' => $competition->infrastructure_provider?->value,
            'external_provider_name' => $competition->external_provider_name,
            'external_entry_url' => $competition->external_entry_url,
            'external_responsibility' => $competition->external_responsibility_accepted_at !== null ? '1' : null,
        ];
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
                    'external_provider_name' => $validated['external_provider_name'] ?? null,
                    'external_entry_url' => $validated['external_entry_url'] ?? null,
                    'external_responsibility_accepted_at' => ! empty($validated['external_responsibility']) ? now() : null,
                ];
            } else {
                $attributes += [
                    'external_provider_name' => null,
                    'external_entry_url' => null,
                    'external_responsibility_accepted_at' => null,
                ];
            }

            $competition->update($attributes);
        }
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $external = request()->input('infrastructure_provider', $competition->infrastructure_provider?->value) === CompetitionInfrastructureProvider::External->value;

        return [
            'infrastructure_provider' => [
                $isDraftSave ? 'nullable' : 'required',
                Rule::enum(CompetitionInfrastructureProvider::class),
            ],
            'external_provider_name' => [$external && ! $isDraftSave ? 'required' : 'nullable', 'string', 'max:255'],
            'external_entry_url' => [$external && ! $isDraftSave ? 'required' : 'nullable', 'url:http,https', 'max:2048'],
            'external_responsibility' => [$external && ! $isDraftSave ? 'accepted' : 'nullable'],
        ];
    }
}
