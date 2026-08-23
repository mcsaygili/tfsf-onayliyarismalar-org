<?php

namespace App\Support\CompetitionWizard;

use App\Enums\CompetitionAudience;
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

    public function fillable(): array
    {
        return ['audience'];
    }

    public function rules(bool $isDraftSave): array
    {
        return [
            'audience' => [
                $isDraftSave ? 'nullable' : 'required',
                Rule::enum(CompetitionAudience::class),
            ],
        ];
    }
}
