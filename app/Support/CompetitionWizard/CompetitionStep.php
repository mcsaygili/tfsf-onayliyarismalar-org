<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;

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

    /** Bu adım mevcut yarışma seçimleri için sihirbazda gösterilmeli mi? */
    public function isApplicable(Competition $competition): bool;

    /** @return array<string, mixed> */
    public function data(Competition $competition): array;

    /** @param array<string, mixed> $validated */
    public function persist(Competition $competition, array $validated): void;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(bool $isDraftSave, Competition $competition): array;
}
