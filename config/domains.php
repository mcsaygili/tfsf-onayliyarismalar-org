<?php

/**
 * Modül subdomain host'ları — ortama göre (yerel / production) env'den yönetilir.
 *
 * Route::domain(config('domains.x')) ile kullanılır. ÖNEMLİ: route'larda env()
 * DEĞİL config() kullanılmalı; çünkü `php artisan route:cache` + `config:cache`
 * sonrası env() yalnızca config dosyaları derlenirken okunur.
 *
 * Yerel varsayılan base: tfsfoy.local. Production'da .env'de:
 *   APP_DOMAIN=tfsfonayliyarismalar.org
 *   DOMAIN_KURUM=kurum.tfsfonayliyarismalar.org
 * gibi override edilir.
 */
$base = env('APP_DOMAIN', 'tfsfoy.local');

return [
    'base' => $base,
    // `env('DOMAIN_UYE', $base)` DEĞİL `?:` kullanılıyor: .env'de `DOMAIN_UYE=`
    // (boş string) satırı env() için "ayarlanmış" sayılır ve varsayılana
    // düşmez — `?:` boş string'i de "yok" gibi ele alıp $base'e düşürür.
    'uye' => env('DOMAIN_UYE') ?: $base,
    // Kod tarafında (guard/model/route adları) Türkçe kullanılmıyor, ama
    // subdomain'in kendisi bir URL/marka kararı — mevcut DNS/hosts kaydıyla
    // (kurum.tfsfoy.local) ve üretim planıyla tutarlı kalması için "kurum."
    // değeri korunuyor.
    'institution' => env('DOMAIN_INSTITUTION', 'kurum.'.$base),
    'temsilci' => env('DOMAIN_TEMSILCI', 'temsilci.'.$base),
    'juri' => env('DOMAIN_JURI', 'juri.'.$base),

    // Faz dışı (Administrator/EYS, Sonuc modülleri bu fazda yok):
    // 'eys' => env('DOMAIN_EYS', 'eys.'.$base),
    // 'sonuc' => env('DOMAIN_SONUC', 'sonuc.'.$base),
];
