<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;

/** Adım 11 — Salt-okunur başvuru özeti ve gönderim hazırlık denetimi. */
class Step11 implements CompetitionStep
{
    public function number(): int
    {
        return 11;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.11.label');
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
