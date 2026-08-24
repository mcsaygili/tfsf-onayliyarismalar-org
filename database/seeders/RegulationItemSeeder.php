<?php

namespace Database\Seeders;

use App\Models\RegulationItem;
use App\Models\RegulationSection;
use Illuminate\Database\Seeder;

class RegulationItemSeeder extends Seeder
{
    private const ITEMS = [
        ['section' => 'competition-name', 'code' => 'competition-name', 'type' => 'source', 'source' => 'competition.name', 'order' => 10, 'tr' => 'Yarışma adı', 'en' => 'Competition name'],
        ['section' => 'purpose-subject', 'code' => 'competition-subject', 'type' => 'source', 'source' => 'competition.subject', 'order' => 10, 'tr' => 'Yarışmanın konusu', 'en' => 'Competition subject'],
        ['section' => 'purpose-subject', 'code' => 'competition-purpose', 'type' => 'source', 'source' => 'competition.purpose', 'order' => 20, 'tr' => 'Yarışmanın amacı', 'en' => 'Competition purpose'],
        ['section' => 'organization', 'code' => 'competition-organizer', 'type' => 'source', 'source' => 'competition.organizer', 'order' => 10, 'tr' => 'Düzenleyen kurum', 'en' => 'Organizing institution'],
        ['section' => 'organization', 'code' => 'competition-partners', 'type' => 'source', 'source' => 'competition.partners', 'order' => 20, 'tr' => 'Paydaş ve işbirlikçileri', 'en' => 'Stakeholders and collaborators'],
        ['section' => 'categories', 'code' => 'competition-categories', 'type' => 'source', 'source' => 'competition.categories', 'order' => 10, 'tr' => 'Kategori ve katılım kuralları', 'en' => 'Category and eligibility rules'],
        ['section' => 'conditions', 'code' => 'capture-regions', 'type' => 'source', 'source' => 'competition.capture_regions', 'order' => 10, 'conditions' => ['competition_type' => ['photographers-marathon']], 'tr' => 'Fotoğraf çekim bölgeleri', 'en' => 'Photography areas'],
        ['section' => 'conditions', 'code' => 'institution-special-conditions', 'type' => 'institution_input', 'source' => null, 'order' => 50, 'tr' => 'Kuruma özel katılım koşulları', 'en' => 'Institution-specific entry conditions'],
        ['section' => 'copyright', 'code' => 'copyright-fixed', 'type' => 'fixed', 'source' => null, 'order' => 10, 'tr' => 'Katılımcı, yüklediği eserlerin kendisine ait olduğunu ve şartnamede belirtilen kullanım haklarını düzenleyen kuruma verdiğini kabul eder.', 'en' => 'The participant confirms ownership of submitted works and grants the organizing institution the usage rights stated in these regulations.'],
        ['section' => 'calendar', 'code' => 'competition-calendar', 'type' => 'source', 'source' => 'competition.schedule', 'order' => 10, 'tr' => 'Yarışma takvimi', 'en' => 'Competition calendar'],
    ];

    public function run(): void
    {
        foreach (self::ITEMS as $definition) {
            $section = RegulationSection::where('code', $definition['section'])->firstOrFail();
            $item = RegulationItem::withTrashed()->firstOrNew(['code' => $definition['code']]);
            $item->fill([
                'regulation_section_id' => $section->id,
                'content_type' => $definition['type'],
                'source_key' => $definition['source'],
                'conditions' => $definition['conditions'] ?? null,
                'sort_order' => $definition['order'],
                'status' => true,
                'is_system' => true,
            ]);
            $item->deleted_at = null;
            $item->save();
            $item->upsertTranslations([
                'tr' => ['content' => $definition['tr']],
                'en' => ['content' => $definition['en']],
            ]);
        }
    }
}
