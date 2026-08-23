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
                'photo_categories' => [
                    'label' => 'Fotoğraf Kategorisi Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'photo_techniques' => [
                    'label' => 'Fotoğraf Çekim Tekniği Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'equipment_types' => [
                    'label' => 'Ekipman Türü Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'equipment_brands' => [
                    'label' => 'Marka Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'equipment_models' => [
                    'label' => 'Ekipman Modeli Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'regulation_sections' => [
                    'label' => 'Şartname Bölümü Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'regulation_items' => [
                    'label' => 'Şartname Maddesi Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'competition_types' => [
                    'label' => 'Yarışma Türü Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'participant_approval_processes' => [
                    'label' => 'Katılımcı Onay Süreci Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'participant_genders' => ['label' => 'Katılımcı Cinsiyetleri', 'actions' => ['manage']],
                'age_eligibility_rules' => ['label' => 'Yaş Uygunluk Kuralları', 'actions' => ['manage']],
                'member_groups' => ['label' => 'Üye Grupları', 'actions' => ['manage']],
                'capture_devices' => ['label' => 'Fotoğraf Üretim Cihazları', 'actions' => ['manage']],
                'file_manager' => [
                    'label' => 'Dosya Yöneticisi',
                    'actions' => ['view', 'create', 'delete', 'manage'],
                ],
                'mail_client' => [
                    'label' => 'Mail İstemcisi',
                    'actions' => ['view', 'manage'],
                ],
                'system_settings' => [
                    'label' => 'Sistem Ayarları',
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
                'institutions' => [
                    'label' => 'Kurum Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'institution_staff' => [
                    'label' => 'Kurum Yetkilisi Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
                'competitions' => [
                    'label' => 'Yarışma Başvurusu Yönetimi',
                    'actions' => ['view', 'manage', 'approve', 'reject', 'request_info'],
                ],
            ],
        ],

        'Temsilci' => [
            'components' => [
                'dashboard' => [
                    'label' => 'Gösterge Paneli',
                    'actions' => ['view'],
                ],
                'representatives' => [
                    'label' => 'Temsilci Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
            ],
        ],

        'Juri' => [
            'components' => [
                'dashboard' => [
                    'label' => 'Gösterge Paneli',
                    'actions' => ['view'],
                ],
                'jurors' => [
                    'label' => 'Jüri Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
                ],
            ],
        ],

        'Uye' => [
            'components' => [
                'dashboard' => [
                    'label' => 'Gösterge Paneli',
                    'actions' => ['view'],
                ],
                'members' => [
                    'label' => 'Üye Yönetimi',
                    'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
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
        'approve' => 'Onaylama',
        'reject' => 'Reddetme',
        'request_info' => 'Bilgi Talebi',
    ],

    'default_roles' => [
        'admin' => ['label' => 'Yönetici', 'actions' => null], // null = modüldeki tüm aksiyonlar
        'viewer' => ['label' => 'Görüntüleyici', 'actions' => ['view']],
    ],

];
