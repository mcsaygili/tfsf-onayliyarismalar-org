<?php

namespace App\Support\CompetitionWizard;

/**
 * Yarışma ekleme sihirbazının tek bir adımını tanımlar (bkz. proje planı
 * "Kurum Paneli — Yarışma Ekleme Sihirbazı"). Yeni bir adım eklemek =
 * bu arayüzü uygulayan bir sınıf yazıp CompetitionStepRegistry::all()'a
 * eklemek — sihirbaz/onay altyapısına dokunulmaz.
 */
interface CompetitionStep
{
    public function number(): int;

    public function label(): string;

    /**
     * Henüz gerçek alanları tasarlanmamış adımlar (şu an 3-10) için false
     * döner — view'da "yakında" içeriği gösterilir, rules() boş kalır.
     */
    public function isImplemented(): bool;

    /**
     * Bu adımın yazdığı Competition kolonları.
     *
     * @return array<int, string>
     */
    public function fillable(): array;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(bool $isDraftSave): array;
}
