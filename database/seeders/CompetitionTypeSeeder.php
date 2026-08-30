<?php

namespace Database\Seeders;

use App\Models\CompetitionType;
use Illuminate\Database\Seeder;

class CompetitionTypeSeeder extends Seeder
{
    private const TYPES = [
        [
            'code' => 'standard',
            'icon_key' => 'competition-standard',
            'sort_order' => 10,
            'requires_location' => false,
            'requires_approval_process' => false,
            'tr' => [
                'name' => 'Standart Yarışma',
                'description' => 'Belirlenen tema ve kategoriler kapsamında eserlerin çevrim içi yüklendiği ve jüri tarafından değerlendirildiği klasik yarışma modelidir.',
            ],
            'en' => [
                'name' => 'Standard Competition',
                'description' => 'The classic competition model in which entries are uploaded online within defined themes and categories and evaluated by a jury.',
            ],
        ],
        [
            'code' => 'photographers-marathon',
            'icon_key' => 'competition-marathon',
            'sort_order' => 20,
            'requires_location' => true,
            'requires_approval_process' => true,
            'tr' => [
                'name' => 'Fotoğrafçılar Maratonu',
                'description' => 'Belirli bir süre ve alanda, ilan edilen konu başlıklarına göre fotoğraf üretilen etkinlik tabanlı yarışma modelidir.',
            ],
            'en' => [
                'name' => 'Photographers Marathon',
                'description' => 'An event-based competition model in which photographs are produced within a defined time and area according to announced themes.',
            ],
        ],
        [
            'code' => 'cup',
            'icon_key' => 'competition-cup',
            'sort_order' => 30,
            'requires_location' => false,
            'requires_approval_process' => false,
            'tr' => [
                'name' => 'Kupa Yarışması',
                'description' => 'Katılımcıların birden fazla etap veya kategoride elde ettiği sonuçların toplam puanla değerlendirildiği yarışma modelidir.',
            ],
            'en' => [
                'name' => 'Cup Competition',
                'description' => 'A competition model in which results achieved across multiple stages or categories are evaluated through an overall score.',
            ],
        ],
        [
            'code' => 'biennial-team-selection',
            'icon_key' => 'competition-biennial',
            'sort_order' => 40,
            'requires_location' => false,
            'requires_approval_process' => false,
            'tr' => [
                'name' => 'Bienal (Takım Seçmesi)',
                'description' => 'Kurum, dernek veya ülke takımlarının seçilmiş fotoğraf setleriyle temsil edildiği ve takım başarısının değerlendirildiği yarışma modelidir.',
            ],
            'en' => [
                'name' => 'Biennial (Team Selection)',
                'description' => 'A competition model in which institution, association, or national teams are represented by selected photo sets and evaluated as teams.',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            $competitionType = CompetitionType::withTrashed()->firstOrNew(['code' => $type['code']]);
            $competitionType->fill([
                'sort_order' => $type['sort_order'],
                'icon_key' => $type['icon_key'],
                'requires_location' => $type['requires_location'],
                'requires_approval_process' => $type['requires_approval_process'],
                'status' => true,
                'is_system' => true,
            ]);
            $competitionType->save();

            if ($competitionType->trashed()) {
                $competitionType->restore();
            }

            $competitionType->upsertTranslations([
                'tr' => $type['tr'],
                'en' => $type['en'],
            ]);
        }
    }
}
