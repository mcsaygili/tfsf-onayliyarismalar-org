<?php

namespace Database\Seeders;

use App\Models\RegulationItem;
use App\Models\RegulationSection;
use Illuminate\Database\Seeder;

class RegulationItemSeeder extends Seeder
{
    private const ITEMS = [
        ['section' => 'competition-name', 'code' => 'competition-name', 'scope' => 'once', 'order' => 10,
            'tr' => 'Bu şartname, {{ competition.name }} için düzenlenmiştir.',
            'en' => 'These regulations have been prepared for {{ competition.name }}.'],
        ['section' => 'purpose-subject', 'code' => 'competition-subject', 'scope' => 'once', 'order' => 10,
            'tr' => '{{ competition.name }} yarışmasının konusu “{{ competition.subject }}” olarak belirlenmiştir.',
            'en' => 'The subject of {{ competition.name }} is “{{ competition.subject }}”.'],
        ['section' => 'purpose-subject', 'code' => 'competition-purpose', 'scope' => 'once', 'order' => 20,
            'tr' => 'Yarışmanın amacı {{ competition.purpose }}',
            'en' => 'The purpose of the competition is {{ competition.purpose }}'],
        ['section' => 'organization', 'code' => 'competition-organizer', 'scope' => 'once', 'order' => 10,
            'tr' => '{{ competition.name }}, {{ institution.name }} tarafından düzenlenmektedir.',
            'en' => '{{ competition.name }} is organized by {{ institution.name }}.'],
        ['section' => 'organization', 'code' => 'competition-partners', 'scope' => 'once', 'order' => 20,
            'conditions' => ['all' => [['field' => 'competition.partners', 'operator' => 'not_empty']]],
            'tr' => 'Yarışmanın paydaş ve işbirlikçileri: {{ competition.partners }}.',
            'en' => 'Competition stakeholders and collaborators: {{ competition.partners }}.'],
        ['section' => 'conditions', 'code' => 'audience-national', 'scope' => 'once', 'order' => 10,
            'conditions' => ['all' => [['field' => 'competition.audience', 'operator' => 'equals', 'value' => 'national']]],
            'tr' => '{{ competition.name }}, ulusal düzeyde düzenlenen bir yarışmadır ve yalnızca geçerli Türkiye Cumhuriyeti kimlik numarası taşıyan T.C. vatandaşlarının katılımına açıktır.',
            'en' => '{{ competition.name }} is a national competition open only to citizens of the Republic of Türkiye holding a valid Turkish identity number.'],
        ['section' => 'conditions', 'code' => 'audience-international', 'scope' => 'once', 'order' => 11,
            'conditions' => ['all' => [['field' => 'competition.audience', 'operator' => 'equals', 'value' => 'international']]],
            'tr' => '{{ competition.name }}, Türkiye’den ve dünyanın tüm ülkelerinden katılıma açık uluslararası bir yarışmadır. Katılım sırasında T.C. kimlik numarası veya farklı bir kimlik doğrulaması yapılmayacaktır.',
            'en' => '{{ competition.name }} is an international competition open to participants from Türkiye and all countries worldwide. No Turkish identity number or other identity verification will be required during entry.'],
        ['section' => 'conditions', 'code' => 'infrastructure-tfsf', 'scope' => 'once', 'order' => 20,
            'conditions' => ['all' => [['field' => 'competition.infrastructure_provider', 'operator' => 'equals', 'value' => 'tfsf']]],
            'tr' => 'Yarışma, TFSF alt yapısı kullanılarak yürütülecektir.',
            'en' => 'The competition will be conducted using the TFSF infrastructure.'],
        ['section' => 'conditions', 'code' => 'infrastructure-external', 'scope' => 'once', 'order' => 21,
            'conditions' => ['all' => [['field' => 'competition.infrastructure_provider', 'operator' => 'equals', 'value' => 'external']]],
            'tr' => 'Yarışma, TFSF alt yapısı kullanılmadan düzenleyici kurumun sorumluluğunda yürütülecektir.',
            'en' => 'The competition will be conducted under the organizing institution’s responsibility without using the TFSF infrastructure.'],
        ['section' => 'conditions', 'code' => 'competition-type', 'scope' => 'once', 'order' => 30,
            'conditions' => ['all' => [['field' => 'competition.infrastructure_provider', 'operator' => 'equals', 'value' => 'tfsf']]],
            'tr' => 'Yarışma türü “{{ competition.type_name }}” olarak belirlenmiştir.',
            'en' => 'The competition type is defined as “{{ competition.type_name }}”.'],
        ['section' => 'conditions', 'code' => 'capture-region', 'scope' => 'capture_region', 'order' => 40,
            'conditions' => ['all' => [['field' => 'competition.type_code', 'operator' => 'equals', 'value' => 'photographers-marathon']]],
            'tr' => 'Yarışmaya yüklenecek fotoğrafların {{ capture_region.name }} sınırları içinde çekilmiş olması gerekir.',
            'en' => 'Photographs submitted to the competition must have been taken within {{ capture_region.name }}.'],
        ['section' => 'conditions', 'code' => 'participant-approval-process', 'scope' => 'once', 'order' => 50,
            'conditions' => ['all' => [['field' => 'competition.approval_process_code', 'operator' => 'not_empty']]],
            'tr' => 'Katılımcı başvuruları “{{ competition.approval_process_name }}” onay sürecine tabidir.',
            'en' => 'Participant applications are subject to the “{{ competition.approval_process_name }}” approval process.'],
        ['section' => 'categories', 'code' => 'category-definition', 'scope' => 'category', 'order' => 10,
            'tr' => '“{{ category.name }}” kategorisinde cinsiyet koşulu {{ category.genders }}, yaş koşulu ise {{ category.age_rule }} olarak uygulanır.',
            'en' => 'For the “{{ category.name }}” category, the gender requirement is {{ category.genders }} and the age requirement is {{ category.age_rule }}.'],
        ['section' => 'categories', 'code' => 'category-member-groups', 'scope' => 'category', 'order' => 20,
            'conditions' => ['all' => [['field' => 'category.member_groups', 'operator' => 'not_empty']]],
            'tr' => '“{{ category.name }}” kategorisine şu üye grupları katılabilir: {{ category.member_groups }}.',
            'en' => 'The following member groups may enter the “{{ category.name }}” category: {{ category.member_groups }}.'],
        ['section' => 'categories', 'code' => 'category-devices', 'scope' => 'category', 'order' => 30,
            'conditions' => ['all' => [['field' => 'category.capture_devices', 'operator' => 'not_empty']]],
            'tr' => '“{{ category.name }}” kategorisinde eserler şu cihazlarla üretilebilir: {{ category.capture_devices }}.',
            'en' => 'Works in the “{{ category.name }}” category may be produced with: {{ category.capture_devices }}.'],
        ['section' => 'categories', 'code' => 'category-processing', 'scope' => 'category', 'order' => 40,
            'conditions' => ['all' => [['field' => 'category.processing_methods', 'operator' => 'not_empty']]],
            'tr' => '“{{ category.name }}” kategorisinde izin verilen düzenleme yöntemleri: {{ category.processing_methods }}.',
            'en' => 'Permitted processing methods for the “{{ category.name }}” category: {{ category.processing_methods }}.'],
        ['section' => 'awards', 'code' => 'category-award', 'scope' => 'award', 'order' => 10,
            'tr' => '“{{ award.category_name }}” kategorisinde {{ award.quantity }} adet “{{ award.name }}” ödülü verilecektir.',
            'en' => '{{ award.quantity }} “{{ award.name }}” award(s) will be presented in the “{{ award.category_name }}” category.'],
        ['section' => 'jury', 'code' => 'category-juror', 'scope' => 'juror', 'order' => 10,
            'tr' => '“{{ juror.category_name }}” kategorisi seçici kurulunda {{ juror.name }} yer alacaktır.',
            'en' => '{{ juror.name }} will serve on the selection committee for the “{{ juror.category_name }}” category.'],
        ['section' => 'calendar', 'code' => 'competition-calendar', 'scope' => 'once', 'order' => 10,
            'tr' => 'Başvurular {{ competition.application_starts_at }} tarihinde başlayacak, {{ competition.application_ends_at }} tarihinde sona erecektir. Yarışma {{ competition.competition_ends_at }} tarihinde tamamlanacaktır.',
            'en' => 'Entries open on {{ competition.application_starts_at }} and close on {{ competition.application_ends_at }}. The competition concludes on {{ competition.competition_ends_at }}.'],
        ['section' => 'conditions', 'code' => 'institution-special-conditions', 'type' => 'institution_input', 'scope' => 'once', 'required' => false, 'order' => 90,
            'tr' => 'Kuruma özel katılım koşulları', 'en' => 'Institution-specific entry conditions'],
        ['section' => 'copyright', 'code' => 'copyright-fixed', 'type' => 'fixed', 'scope' => 'once', 'order' => 10,
            'tr' => 'Katılımcı, yüklediği eserlerin kendisine ait olduğunu ve şartnamede belirtilen kullanım haklarını düzenleyen kuruma verdiğini kabul eder.',
            'en' => 'The participant confirms ownership of submitted works and grants the organizing institution the usage rights stated in these regulations.'],
    ];

    public function run(): void
    {
        RegulationItem::query()
            ->where('is_system', true)
            ->whereIn('code', ['competition-categories', 'capture-regions'])
            ->update(['status' => false]);

        foreach (self::ITEMS as $definition) {
            $section = RegulationSection::where('code', $definition['section'])->firstOrFail();
            $item = RegulationItem::withTrashed()->firstOrNew(['code' => $definition['code']]);
            $item->fill([
                'regulation_section_id' => $section->id,
                'content_type' => $definition['type'] ?? 'template',
                'render_scope' => $definition['scope'],
                'source_key' => null,
                'conditions' => $definition['conditions'] ?? null,
                'is_required' => $definition['required'] ?? true,
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
