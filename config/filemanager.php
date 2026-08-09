<?php

/**
 * FileManager (dosya-sistemi tabanlı medya yöneticisi) yapılandırması.
 * Tüm yollar 'public' diskine (storage/app/public) görelidir.
 */
return [
    'disk' => 'public',

    /**
     * Context (kapsam) → kök alt-klasör eşlemesi (modül-bazlı izolasyon).
     * 'all' = tüm disk (yalnız eys.file_manager.manage izni olan EYS
     * yöneticisi). Her context'in 'module' alanı erişim kontrolünde
     * kullanılır (App\Enums\Module case adı) — null ise modüle bağlı değil.
     */
    'default_context' => 'common',

    'contexts' => [
        'all' => ['label' => 'Tümü', 'root' => '', 'module' => null],
        'common' => ['label' => 'Genel', 'root' => 'media', 'module' => null],
        'kurum' => ['label' => 'Kurum', 'root' => 'kurum', 'module' => 'Institution'],
        'temsilci' => ['label' => 'Temsilci', 'root' => 'temsilci', 'module' => 'Temsilci'],
        'juri' => ['label' => 'Jüri', 'root' => 'juri', 'module' => 'Juri'],
        'uye' => ['label' => 'Üye', 'root' => 'uye', 'module' => 'Uye'],
    ],

    'thumb_width' => 240,

    'per_page' => 60,

    'max_upload_mb' => 25,

    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'md', 'json', 'xml', 'zip', 'rar',
    ],

    'image_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'],

    /** Güvenlik: hiçbir koşulda kabul edilmeyecek uzantılar. */
    'blocked_extensions' => [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'sh', 'bash',
        'exe', 'bat', 'cmd', 'com', 'cgi', 'pl', 'py', 'js', 'htaccess',
    ],
];
