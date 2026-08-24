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
        ['code' => 'competition-name', 'sort_order' => 10, 'tr' => 'Yarışmanın Adı', 'en' => 'Competition Name'],
        ['code' => 'purpose-subject', 'sort_order' => 20, 'tr' => 'Yarışmanın Amacı ve Konusu', 'en' => 'Competition Purpose and Subject'],
        ['code' => 'organization', 'sort_order' => 30, 'tr' => 'Yarışma Organizasyonu', 'en' => 'Competition Organization'],
        ['code' => 'categories', 'sort_order' => 40, 'tr' => 'Yarışma Kategorileri', 'en' => 'Competition Categories'],
        ['code' => 'conditions', 'sort_order' => 50, 'tr' => 'Yarışma Koşulları', 'en' => 'Competition Conditions'],
        ['code' => 'copyright', 'sort_order' => 60, 'tr' => 'Telif (Kullanım) Hakkı', 'en' => 'Copyright (Usage Rights)'],
        ['code' => 'other', 'sort_order' => 70, 'tr' => 'Diğer Hususlar', 'en' => 'Other Matters'],
        ['code' => 'submission', 'sort_order' => 80, 'tr' => 'Eserlerin Gönderilmesi/Yüklenmesi', 'en' => 'Submission/Upload of Works'],
        ['code' => 'jury', 'sort_order' => 90, 'tr' => 'Seçici Kurul', 'en' => 'Selection Committee'],
        ['code' => 'representative', 'sort_order' => 100, 'tr' => 'TFSF Yarışma Temsilcisi', 'en' => 'TFSF Competition Representative'],
        ['code' => 'calendar', 'sort_order' => 110, 'tr' => 'Yarışma Takvimi', 'en' => 'Competition Calendar'],
        ['code' => 'awards', 'sort_order' => 120, 'tr' => 'Derece ve Sergileme Ödülleri', 'en' => 'Awards and Exhibition Prizes'],
        ['code' => 'manager', 'sort_order' => 130, 'tr' => 'Düzenleyici Kurum Yarışma Sorumlusu', 'en' => 'Organizing Institution Competition Manager'],
        ['code' => 'approval-number', 'sort_order' => 140, 'tr' => 'Onay Numarası', 'en' => 'Approval Number'],
        ['code' => 'title-usage', 'sort_order' => 150, 'tr' => 'Unvan Kullanımı', 'en' => 'Title Usage'],
    ];

    public function run(): void
    {
        foreach (self::SECTIONS as $section) {
            $regulationSection = RegulationSection::query()
                ->whereHas('translations', fn ($q) => $q->where('locale', 'tr')->where('name', $section['tr']))
                ->first();

            if (! $regulationSection) {
                $regulationSection = RegulationSection::create([
                    'code' => $section['code'],
                    'sort_order' => $section['sort_order'],
                    'status' => true,
                    'is_system' => true,
                ]);
            } else {
                $regulationSection->update(['code' => $section['code'], 'sort_order' => $section['sort_order'], 'is_system' => true]);
            }

            $regulationSection->upsertTranslations([
                'tr' => ['name' => $section['tr']],
                'en' => ['name' => $section['en']],
            ]);
        }
    }
}
