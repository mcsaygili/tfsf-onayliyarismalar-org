<?php

/**
 * Bileşen-bazlı izin kayıt defteri — hem ModuleRoleSeeder (izin/rol üretimi)
 * hem de Rol/İzin yönetimi ekranlarının gruplaması için tek doğruluk
 * kaynağı. İzin adı şu desende üretilir: `{modul_key}.{bilesen}.{aksiyon}`
 * ör: `eys.users.view` (bkz. App\Enums\Module::key()).
 *
 * 'modules' anahtarları App\Enums\Module case adlarıyla birebir eşleşmeli.
 * Institution/Temsilci/Juri/Uye modülleri enum'da var ama bileşenleri
 * bilinçli olarak boş — EYS'in bu domain'lerin verilerini yönetebilmesi
 * ayrı bir talep olarak ele alınacak (bkz. proje planı).
 */
return [

    'modules' => [
        'Eys' => [
            'components' => [
                'users' => [
                    'label' => 'Kullanıcı Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'roles' => [
                    'label' => 'Rol Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'permissions' => [
                    'label' => 'İzin Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'countries' => [
                    'label' => 'Ülke Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'cities' => [
                    'label' => 'Şehir Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'education_levels' => [
                    'label' => 'Öğrenim Durumu Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'institution_types' => [
                    'label' => 'Kurum Türü Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'file_manager' => [
                    'label' => 'Dosya Yöneticisi',
                    'actions' => ['view', 'create', 'delete', 'manage'],
                ],
                'mail_client' => [
                    'label' => 'Mail İstemcisi',
                    'actions' => ['view', 'manage'],
                ],
            ],
        ],

        'Institution' => [
            'components' => [
                'dashboard' => [
                    'label' => 'Gösterge Paneli',
                    'actions' => ['view'],
                ],
            ],
        ],

        'Temsilci' => [
            'components' => [
                'dashboard' => [
                    'label' => 'Gösterge Paneli',
                    'actions' => ['view'],
                ],
            ],
        ],

        'Juri' => [
            'components' => [
                'dashboard' => [
                    'label' => 'Gösterge Paneli',
                    'actions' => ['view'],
                ],
            ],
        ],

        'Uye' => [
            'components' => [
                'dashboard' => [
                    'label' => 'Gösterge Paneli',
                    'actions' => ['view'],
                ],
            ],
        ],
    ],

    'action_labels' => [
        'view' => 'Görüntüleme',
        'create' => 'Oluşturma',
        'edit' => 'Düzenleme',
        'delete' => 'Silme',
        'manage' => 'Tam Yönetim',
    ],

    'default_roles' => [
        'admin' => ['label' => 'Yönetici', 'actions' => null], // null = modüldeki tüm aksiyonlar
        'viewer' => ['label' => 'Görüntüleyici', 'actions' => ['view']],
    ],

];
