<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;

/**
 * Alanları henüz tasarlanmamış adımlar için (şu an 3-10) — "ileri"
 * tıklanınca hep geçer (rules() boş), view "yakında" içeriği gösterir.
 * Bir adımın gerçek alanları belirlenince bu sınıfın yerine kendi
 * CompetitionStep implementasyonu (Step1 gibi) geçirilir.
 */
class PlaceholderStep implements CompetitionStep
{
    public function __construct(
        private readonly int $number,
    ) {}

    public function number(): int
    {
        return $this->number;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.'.$this->number.'.label');
    }

    public function isImplemented(): bool
    {
        return false;
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
        // Henüz uygulanmamış adımlar veri yazmaz.
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        return [];
    }
}
