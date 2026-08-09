<?php

namespace App\Enums;

/**
 * EYS'in yönetebileceği "modüller" — her biri bu projenin mevcut bir
 * guard'ına karşılık gelir. Spatie laravel-permission'ın "team" özelliği
 * bu enum'un int değerini team_id olarak kullanıyor (bkz. config/permission.php),
 * yani bir EYS rolü belirli bir modüle (guard'a değil, modüle) bağlanıyor.
 *
 * Sadece Eys guard'ı (App\Models\EysUser) rol/izin alıyor — Institution/
 * Temsilci/Juri/Uye kendi kullanıcılarına rol/izin sistemi eklenmiyor; bu
 * modüller enum'da EYS'in ileride bu domain'lerin verilerini yönetebilmesi
 * için (bkz. proje planı — "Kapsam Dışı" bölümü) hazır bulunuyor, ama
 * config/permissions.php'de şimdilik bileşenleri boş.
 */
enum Module: int
{
    case Institution = 1;
    case Temsilci = 2;
    case Juri = 3;
    case Uye = 4;
    case Eys = 5;

    public function label(): string
    {
        return match ($this) {
            self::Institution => __('eys.module_names.Institution'),
            self::Temsilci => __('eys.module_names.Temsilci'),
            self::Juri => __('eys.module_names.Juri'),
            self::Uye => __('eys.module_names.Uye'),
            self::Eys => __('eys.module_names.Eys'),
        };
    }

    /**
     * İzin adlarında kullanılan İngilizce kısa slug (ör. `institution.staff.view`).
     * Bilinçli olarak subdomain string'lerinden (kurum/temsilci/juri) farklı —
     * bu proje kod/DB tarafında Türkçe kullanmıyor (bkz. config/domains.php).
     */
    public function key(): string
    {
        return match ($this) {
            self::Institution => 'institution',
            self::Temsilci => 'representative',
            self::Juri => 'jury',
            self::Uye => 'member',
            self::Eys => 'eys',
        };
    }

    public function domain(): string
    {
        return match ($this) {
            self::Institution => config('domains.institution'),
            self::Temsilci => config('domains.temsilci'),
            self::Juri => config('domains.juri'),
            self::Uye => config('domains.uye'),
            self::Eys => config('domains.eys'),
        };
    }

    public static function fromSubdomain(string $host): self
    {
        return match ($host) {
            config('domains.institution') => self::Institution,
            config('domains.temsilci') => self::Temsilci,
            config('domains.juri') => self::Juri,
            config('domains.eys') => self::Eys,
            default => self::Uye,
        };
    }
}
