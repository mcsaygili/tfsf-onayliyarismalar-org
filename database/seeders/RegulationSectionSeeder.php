<?php

namespace Database\Seeders;

use App\Models\RegulationSection;
use Illuminate\Database\Seeder;

/**
 * Yarışma Sistemi — standart TFSF şartname bölüm başlıkları. Örnek
 * şartnameden (TFSF 2026-020) çıkarılan 15 bölüm; madde içerikleri
 * bilinçli olarak seed edilmiyor, admin panelinden elle girilecek.
 */
class RegulationSectionSeeder extends Seeder
{
    private const SECTIONS = [
        ['sort_order' => 10, 'tr' => 'Yarışmanın Adı', 'en' => 'Competition Name'],
        ['sort_order' => 20, 'tr' => 'Yarışmanın Amacı ve Konusu', 'en' => 'Competition Purpose and Subject'],
        ['sort_order' => 30, 'tr' => 'Yarışma Organizasyonu', 'en' => 'Competition Organization'],
        ['sort_order' => 40, 'tr' => 'Yarışma Kategorileri', 'en' => 'Competition Categories'],
        ['sort_order' => 50, 'tr' => 'Yarışma Koşulları', 'en' => 'Competition Conditions'],
        ['sort_order' => 60, 'tr' => 'Telif (Kullanım) Hakkı', 'en' => 'Copyright (Usage Rights)'],
        ['sort_order' => 70, 'tr' => 'Diğer Hususlar', 'en' => 'Other Matters'],
        ['sort_order' => 80, 'tr' => 'Eserlerin Gönderilmesi/Yüklenmesi', 'en' => 'Submission/Upload of Works'],
        ['sort_order' => 90, 'tr' => 'Seçici Kurul', 'en' => 'Selection Committee'],
        ['sort_order' => 100, 'tr' => 'TFSF Yarışma Temsilcisi', 'en' => 'TFSF Competition Representative'],
        ['sort_order' => 110, 'tr' => 'Yarışma Takvimi', 'en' => 'Competition Calendar'],
        ['sort_order' => 120, 'tr' => 'Derece ve Sergileme Ödülleri', 'en' => 'Awards and Exhibition Prizes'],
        ['sort_order' => 130, 'tr' => 'Düzenleyici Kurum Yarışma Sorumlusu', 'en' => 'Organizing Institution Competition Manager'],
        ['sort_order' => 140, 'tr' => 'Onay Numarası', 'en' => 'Approval Number'],
        ['sort_order' => 150, 'tr' => 'Unvan Kullanımı', 'en' => 'Title Usage'],
    ];

    public function run(): void
    {
        foreach (self::SECTIONS as $section) {
            $regulationSection = RegulationSection::query()
                ->whereHas('translations', fn ($q) => $q->where('locale', 'tr')->where('name', $section['tr']))
                ->first();

            if (! $regulationSection) {
                $regulationSection = RegulationSection::create([
                    'sort_order' => $section['sort_order'],
                    'status' => true,
                ]);
            } else {
                $regulationSection->update(['sort_order' => $section['sort_order']]);
            }

            $regulationSection->upsertTranslations([
                'tr' => ['name' => $section['tr']],
                'en' => ['name' => $section['en']],
            ]);
        }
    }
}
