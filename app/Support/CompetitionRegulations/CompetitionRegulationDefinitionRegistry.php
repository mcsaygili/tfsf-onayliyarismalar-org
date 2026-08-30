<?php

namespace App\Support\CompetitionRegulations;

class CompetitionRegulationDefinitionRegistry
{
    /** @return array<string, string> */
    public function renderScopes(): array
    {
        return [
            'once' => 'Yarışma için bir kez',
            'category' => 'Her kategori için',
            'award' => 'Her ödül için',
            'capture_region' => 'Her çekim bölgesi için',
            'juror' => 'Her jüri için',
        ];
    }

    /** @return array<string, array{label: string, options?: array<string, string>}> */
    public function conditionFields(): array
    {
        return [
            'competition.audience' => ['label' => 'Yarışma kitlesi', 'options' => ['national' => 'Ulusal', 'international' => 'Uluslararası']],
            'competition.infrastructure_provider' => ['label' => 'Alt yapı sağlayıcısı', 'options' => ['tfsf' => 'TFSF', 'external' => 'Kurum / harici']],
            'competition.type_code' => ['label' => 'Yarışma türü kodu'],
            'competition.partners' => ['label' => 'Paydaşlar'],
            'competition.approval_process_code' => ['label' => 'Katılımcı onay süreci kodu'],
            'category.age_rule_code' => ['label' => 'Kategori yaş kuralı kodu'],
            'category.member_groups' => ['label' => 'Kategori üye grupları'],
            'category.capture_devices' => ['label' => 'Kategori çekim cihazları'],
            'category.processing_methods' => ['label' => 'Kategori düzenleme yöntemleri'],
            'juror.status' => ['label' => 'Jüri kayıt durumu', 'options' => ['registered' => 'Kayıtlı', 'invited' => 'Davet bekliyor']],
        ];
    }

    /** @return array<string, string> */
    public function operators(): array
    {
        return [
            'equals' => 'Eşittir',
            'not_equals' => 'Eşit değildir',
            'in' => 'Şunlardan biridir',
            'exists' => 'Değer mevcut',
            'not_empty' => 'Boş değil',
            'contains' => 'İçerir',
        ];
    }

    /** @return array<string, string> */
    public function tokensForScope(string $scope): array
    {
        $tokens = [
            'competition.name' => 'Yarışma adı',
            'competition.subject' => 'Yarışmanın konusu',
            'competition.purpose' => 'Yarışmanın amacı',
            'competition.partners' => 'Paydaş ve işbirlikçileri',
            'competition.audience_label' => 'Kitle adı',
            'competition.infrastructure_label' => 'Alt yapı sağlayıcısı',
            'competition.type_name' => 'Yarışma türü',
            'competition.approval_process_name' => 'Katılımcı onay süreci',
            'competition.application_starts_at' => 'Başvuru başlangıcı',
            'competition.application_ends_at' => 'Başvuru bitişi',
            'competition.competition_ends_at' => 'Yarışma bitişi',
            'institution.name' => 'Düzenleyen kurum',
        ];

        $scoped = match ($scope) {
            'category' => [
                'category.name' => 'Kategori adı',
                'category.genders' => 'Cinsiyet koşulları',
                'category.age_rule' => 'Yaş koşulu',
                'category.member_groups' => 'Üye grupları',
                'category.capture_devices' => 'Çekim cihazları',
                'category.processing_methods' => 'Düzenleme yöntemleri',
            ],
            'award' => [
                'award.category_name' => 'Kategori adı',
                'award.name' => 'Ödül adı',
                'award.quantity' => 'Ödül adedi',
                'award.special_award_text' => 'Özel ödül açıklaması',
                'award.material_award' => 'Maddi ödül',
            ],
            'capture_region' => [
                'capture_region.city' => 'Şehir',
                'capture_region.country' => 'Ülke',
                'capture_region.name' => 'Şehir ve ülke',
            ],
            'juror' => [
                'juror.category_name' => 'Kategori adı',
                'juror.name' => 'Jüri adı',
                'juror.status_label' => 'Jüri kayıt durumu',
            ],
            default => [],
        };

        return $tokens + $scoped;
    }

    /** @return array<int, string> */
    public function allowedConditionFields(): array
    {
        return array_keys($this->conditionFields());
    }
}
