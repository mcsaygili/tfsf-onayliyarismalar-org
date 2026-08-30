<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;

/** Adım 10 — Salt-okunur başvuru özeti ve gönderim hazırlık denetimi. */
class Step10 implements CompetitionStep
{
    public function number(): int
    {
        return 10;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.10.label');
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
        return [];
    }

    public function persist(Competition $competition, array $validated): void
    {
        // Bu adım veri toplamaz; gönderim CompetitionController tarafından yapılır.
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        return [];
    }
}
