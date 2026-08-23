<?php

namespace App\Support\CompetitionWizard;

use App\Enums\CompetitionAudience;
use App\Models\Competition;
use Illuminate\Validation\Rule;

/**
 * Adım 1 — Yarışma Kitlesi: ulusal/uluslararası seçimi. Bu seçim ilerleyen
 * adımlarda İngilizce içerik alanlarının gerekip gerekmediğini belirler.
 */
class Step1 implements CompetitionStep
{
    public function number(): int
    {
        return 1;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.1.label');
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
        return ['audience' => $competition->audience?->value];
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (array_key_exists('audience', $validated)) {
            $competition->update(['audience' => $validated['audience']]);
        }
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        return [
            'audience' => [
                $isDraftSave ? 'nullable' : 'required',
                Rule::enum(CompetitionAudience::class),
            ],
        ];
    }
}
