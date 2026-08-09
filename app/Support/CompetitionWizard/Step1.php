<?php

namespace App\Support\CompetitionWizard;

/**
 * Adım 1 — Yarışma Bilgileri: Yarışma Adı, Düzenleyen Kurum Paydaş ve
 * İşbirlikçileri, Yarışmanın Konusu, Yarışmanın Amacı. Kullanıcı isteğine
 * göre 4 alan da zorunlu — taslak kaydında (isDraftSave) hepsi nullable.
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
        return ['name', 'partners', 'subject', 'purpose'];
    }

    public function rules(bool $isDraftSave): array
    {
        $required = $isDraftSave ? 'nullable' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'partners' => [$required, 'string', 'max:2000'],
            'subject' => [$required, 'string', 'max:2000'],
            'purpose' => [$required, 'string', 'max:2000'],
        ];
    }
}
