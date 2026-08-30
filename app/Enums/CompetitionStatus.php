<?php

namespace App\Enums;

/**
 * Bir yarışma başvurusunun onay süreci durumu (bkz. proje planı
 * "Kurum Paneli — Yarışma Ekleme Sihirbazı"). Akış:
 *
 *   Draft/NeedsInfo -> Submitted -> UnderReview -> Approved
 *                                      |  |  |
 *                                      |  |  -> Rejected
 *                                      |  -> WaitingRequirements -> Approved
 *                                      -> NeedsInfo -> Submitted
 *
 * Rejected kasıtlı olarak salt-okunur/terminal — kurum düzeltip yeniden
 * gönderemez, yeni bir taslak başlatması gerekir. NeedsInfo ise
 * düzenle-ve-yeniden-gönder döngüsü.
 */
enum CompetitionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case WaitingRequirements = 'waiting_requirements';
    case NeedsInfo = 'needs_info';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Kurum ve EYS panelleri ayrı lang namespace'lerine sahip (institution.*
     * / eys.*), bu yüzden etiket burada sabitlenmiyor — çağıran taraf
     * `__($guardPrefix.'.competitions.status.'.$status->value)` çağırır.
     * Her iki lang dosyasında da aynı anahtarlar (status.draft vb.) var,
     * böylece iki panelde de aynı metinler görünüyor.
     */

    /**
     * `badgeClass()` hem Kurum hem EYS tarafında aynı .ip-badge modifier
     * sınıflarını kullanabilmek için — bkz. components/institution/
     * app-layout.blade.php ve components/eys/app-layout.blade.php'deki
     * .ip-badge.is-draft/.is-pending/.is-needs-info/.is-approved/.is-rejected.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'is-draft',
            self::Submitted => 'is-submitted',
            self::UnderReview => 'is-under-review',
            self::WaitingRequirements => 'is-waiting-requirements',
            self::NeedsInfo => 'is-needs-info',
            self::Approved => 'is-approved',
            self::Rejected => 'is-rejected',
        };
    }

    public function isEditableByInstitution(): bool
    {
        return in_array($this, [self::Draft, self::NeedsInfo], true);
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, match ($this) {
            self::Draft, self::NeedsInfo => [self::Submitted],
            self::Submitted => [self::UnderReview, self::Rejected],
            self::UnderReview => [self::NeedsInfo, self::WaitingRequirements, self::Approved, self::Rejected],
            self::WaitingRequirements => [self::NeedsInfo, self::Approved, self::Rejected],
            self::Approved, self::Rejected => [],
        }, true);
    }
}
