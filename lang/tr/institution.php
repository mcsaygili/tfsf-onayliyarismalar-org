<?php

return [

    'eyebrow' => 'TFSF Onaylı Yarışmalar',

    'maintenance' => [
        'heading' => 'Bakım Çalışması',
        'subheading' => 'Kurum paneli kısa bir süreliğine bakımda.',
        'card_label' => 'Bakım Modu',
        'card_title' => 'Şu anda bakımdayız',
        'default_message' => 'Sistemimizde planlı bir bakım çalışması yürütülmektedir. Lütfen daha sonra tekrar deneyiniz.',
    ],

    'login' => [
        'heading' => 'Kurum Portalı',
        'subheading' => 'Yarışma düzenlemek ve düzenlediğiniz yarışmalarla ilgili işlemleri yönetmek için giriş yapın.',
        'card_label' => 'Oturum Aç',
        'card_title' => 'Hoş geldiniz',
        'email' => 'E-posta',
        'email_placeholder' => 'ornek@kurum.org',
        'password' => 'Şifre',
        'remember' => 'Beni hatırla',
        'forgot_password' => 'Şifrenizi mi unuttunuz?',
        'submit' => 'Giriş Yap',
        'no_account' => 'Kurumunuzu henüz kaydetmediniz mi? Kayıt olun',
    ],

    'register' => [
        'heading' => 'Kurum Kaydı',
        'subheading' => 'Kurumunuzu birkaç saniyede kaydedin, kurum ve yetkili bilgilerini girişten sonra tamamlarsınız.',
        'card_label' => 'Hesap Oluştur',
        'card_title' => 'Kaydınızı başlatın',
        'email' => 'E-posta',
        'email_placeholder' => 'ornek@kurum.org',
        'password' => 'Şifre',
        'confirm_password' => 'Şifre (Tekrar)',
        'submit' => 'Kayıt Ol',
        'have_account' => '← Zaten hesabınız var mı? Giriş yapın',
        'check_email' => 'Kurum kaydınız oluşturuldu. Hesabınızı etkinleştirmek için e-postanıza gönderilen bağlantıya tıklayın.',
    ],

    'verify_email' => [
        'heading' => 'E-postanızı doğrulayın',
        'subheading' => 'Devam etmeden önce e-posta adresinizi doğrulamanız gerekiyor.',
        'card_label' => 'Son Adım',
        'card_title' => 'Gelen kutunuzu kontrol edin',
        'resend' => 'Doğrulama E-postasını Yeniden Gönder',
        'resent' => 'Kayıt sırasında belirttiğiniz e-posta adresine yeni bir doğrulama bağlantısı gönderildi.',
        'logout' => 'Çıkış Yap',
    ],

    'forgot_password' => [
        'heading' => 'Şifrenizi mi unuttunuz?',
        'subheading' => 'E-posta adresinizi bildirin, şifrenizi sıfırlamanız için bir bağlantı gönderelim.',
        'card_label' => 'Şifre Sıfırlama',
        'card_title' => 'Bağlantı gönderelim',
        'email' => 'E-posta',
        'email_placeholder' => 'ornek@kurum.org',
        'submit' => 'Sıfırlama Bağlantısı Gönder',
        'back_to_login' => '← Girişe dön',
    ],

    'reset_password' => [
        'heading' => 'Yeni şifre belirleyin',
        'subheading' => 'Hesabınız için güçlü ve daha önce kullanmadığınız bir şifre seçin.',
        'card_label' => 'Şifre Sıfırlama',
        'card_title' => 'Yeni şifrenizi girin',
        'email' => 'E-posta',
        'new_password' => 'Yeni Şifre',
        'confirm_password' => 'Şifre (Tekrar)',
        'submit' => 'Şifreyi Sıfırla',
        'back_to_login' => '← Girişe dön',
    ],

    'account_disabled' => 'Hesabınız kapalıdır.',

    'nav' => [
        'dashboard' => 'Gösterge Paneli',
        'account' => 'Hesabım',
        'institution_info' => 'Kurum Bilgileri',
        'password' => 'Şifre İşlemleri',
        'staff' => 'Yetkili Bilgileri',
        'competitions' => 'Yarışmalarım',
        'logout' => 'Güvenli Çıkış',
    ],

    'profile' => [
        'institution_section' => 'Kurum Bilgileri',
        'institution_hint' => 'Yarışma düzenleyen kurumunuzla ilgili bilgiler.',
        'institution_name' => 'Kurum Adı',
        'institution_email' => 'Kurum E-postası',
        'institution_phone' => 'Kurum Telefonu',
        'institution_website' => 'Web Sitesi',
        'institution_address' => 'Adres',
        'first_name' => 'Ad',
        'last_name' => 'Soyad',
        'phone' => 'Telefon',
        'save' => 'Kaydet',
        'updated' => 'Bilgileriniz güncellendi.',
    ],

    'password' => [
        'section_title' => 'Şifre İşlemleri',
        'section_hint' => 'Hesabınızın güvenliği için uzun, rastgele bir şifre kullandığınızdan emin olun.',
        'current_password' => 'Mevcut Şifre',
        'new_password' => 'Yeni Şifre',
        'confirm_password' => 'Şifre (Tekrar)',
        'save' => 'Kaydet',
        'updated' => 'Şifreniz güncellendi.',
    ],

    'staff' => [
        'list_title' => 'Yetkili Bilgileri',
        'list_hint' => 'Kurumunuz adına işlem yapabilecek yetkili kişiler. Birden fazla yetkili tanımlayabilirsiniz.',
        'add_new' => '+ Yeni Yetkili Ekle',
        'column_name' => 'Ad Soyad',
        'column_email' => 'E-posta',
        'column_phone' => 'Telefon',
        'column_status' => 'Durum',
        'status_active' => 'Aktif',
        'status_inactive' => 'Pasif',
        'edit_action' => 'Düzenle',
        'empty' => 'Henüz kayıtlı bir yetkili yok.',
        'pagination_info' => ':first–:last / :total kayıt',
        'create_title' => 'Yeni Yetkili Ekle',
        'create_hint' => 'Kurumunuz adına işlem yapabilecek yeni bir yetkili tanımlayın.',
        'edit_title' => 'Yetkili Bilgilerini Düzenle',
        'edit_hint' => 'Bu yetkilinin bilgilerini güncelleyin.',
        'back_to_list' => 'Yetkili listesine dön',
        'password' => 'Şifre',
        'password_confirmation' => 'Şifre (Tekrar)',
        'save_new' => 'Yetkili Ekle',
        'created' => 'Yeni yetkili eklendi.',
        'updated' => 'Yetkili bilgileri güncellendi.',
    ],

    'dashboard' => [
        'incomplete_title' => 'Kurum bilgileriniz eksik',
        'incomplete_text' => 'Kurum adı, e-postası ve telefonu zorunlu bilgilerdir.',
        'incomplete_link' => 'Bilgilerinizi güncelleyiniz',
        'total_staff' => 'Toplam Yetkili',
    ],

    'field_help' => [
        'open' => ':field alanı için yardım göster',
        'close' => 'Yardım penceresini kapat',
        'example' => 'Örnek',
    ],

    'competitions' => [
        'list_title' => 'Yarışmalarım',
        'list_hint' => 'Yarışma başvurularınızı buradan oluşturur ve takip edersiniz.',
        'add_new' => '+ Yeni Başvuru',
        'complete_profile' => 'Kurum Bilgilerini Tamamla',
        'incomplete_profile_title' => 'Yeni yarışma oluşturamazsınız',
        'incomplete_profile_text' => 'Yarışma başvurusu oluşturmadan önce kurum adı, e-postası ve telefonu eksiksiz olmalıdır.',
        'incomplete_profile_link' => 'Kurum bilgilerini tamamlayın.',
        'untitled' => 'İsimsiz Başvuru',
        'column_name' => 'Yarışma Adı',
        'column_status' => 'Durum',
        'column_updated' => 'Son Güncelleme',
        'open_action' => 'Aç',
        'empty' => 'Henüz bir yarışma başvurunuz yok.',
        'pagination_info' => ':first–:last / :total kayıt',

        'status' => [
            'draft' => 'Taslak',
            'pending_review' => 'Onay Bekliyor',
            'needs_info' => 'Ek Bilgi Bekleniyor',
            'approved' => 'Onaylandı',
            'rejected' => 'Reddedildi',
        ],

        'steps' => [
            1 => ['label' => 'Yarışma Kitlesi', 'hint' => 'Yarışmanın hedef kitlesini seçin. Bu seçim, İngilizce içerik girilmesi gerekip gerekmediğini belirler.'],
            2 => ['label' => 'Yarışma Bilgileri', 'hint' => 'Yarışmanızın adı, düzenleyen kurumu, paydaşları, konusu ve amacı.'],
            3 => ['label' => 'Adım 3'],
            4 => ['label' => 'Adım 4'],
            5 => ['label' => 'Adım 5'],
            6 => ['label' => 'Adım 6'],
            7 => ['label' => 'Adım 7'],
            8 => ['label' => 'Adım 8'],
            9 => ['label' => 'Adım 9'],
            10 => ['label' => 'Adım 10'],
        ],

        'fields' => [
            'audience' => 'Yarışma Kitlesi',
            'name' => 'Yarışma Adı',
            'organizing_institution' => 'Düzenleyen Kurum',
            'organizing_institution_hint' => 'Oturum açtığınız kurum bilgisi otomatik olarak kullanılır ve bu alanda değiştirilemez.',
            'partners' => 'Paydaş ve İşbirlikçileri',
            'partners_placeholder' => 'Örn. Kurum A, Kurum B, Kurum C',
            'partners_hint' => 'Opsiyoneldir. Birden fazla paydaş veya işbirlikçiyi virgül (,) ile ayırın.',
            'subject' => 'Yarışmanın Konusu',
            'purpose' => 'Yarışmanın Amacı',
            'characters_remaining' => ':remaining karakter kaldı (en fazla :max).',
        ],

        'audience_definition' => 'Kitle tanımı',
        'audiences' => [
            'national' => [
                'title' => 'Ulusal Yarışma',
                'language' => 'Yalnızca Türkçe içerik',
                'description' => 'Yarışma başvurusu, şartnamesi ve ilgili içerikler yalnızca Türkçe hazırlanır.',
                'definition' => "Ulusal düzeyde düzenlenen yarışmalar sadece geçerli Türkiye Cumhuriyeti kimlik numarası taşıyan T.C. vatandaşlara açıktır.\n\nÇalışma izni veya aynı zamanda ikamet izni yerine geçen “Çalışma İzni Muafiyet Teyit Belgesi” düzenlemeye yetkili kurumlardan; Çalışma ve Sosyal Güvenlik Bakanlığına, Ekonomi Bakanlığına, Kültür ve Turizm Bakanlığına, YÖK Başkanlığına, müracaat eden yabancıların kayıtlarının bu kurumlar tarafından Nüfus ve Vatandaşlık İşleri Genel Müdürlüğüne elektronik ortamda gönderilmesi halinde yabancılara mahsus kimlik numarası almaları mümkündür. Ayrıca Türkiye’de “Vatansız Kişi Kimlik Belgesi” alanlar ve herhangi bir amaçla “en az doksan gün süreli ikamet izni” verilenler, “tutuklu veya hükümlü olarak cezaevlerinde ya da idari gözetim altında geri gönderme merkezlerinde bulunan yabancılar”, “Uluslararası Koruma Başvuru Sahibi Kimlik Belgesi” düzenlenen yabancılar, “geçici koruma” statüsünde bulunan yabancılar ve “Türkiye’de yasal olarak bulunan yabancılar” ın da Göç İdaresi İl Müdürlüklerine müracaatları halinde yabancılara mahsus kimlik numarası almaları mümkündür.\n\nBu tip kimlik numarası taşıyan kişiler bu ulusal yarışmalara katılamazlar. Bu kişiler dilerlerse TFSF onaylı uluslararası yarışmalara katılabilirler.",
            ],
            'international' => [
                'title' => 'Uluslararası Yarışma',
                'language' => 'Türkçe ve İngilizce içerik',
                'description' => 'Yarışma başvurusu, şartnamesi ve ilgili içerikler Türkçe ve İngilizce hazırlanır.',
                'definition' => 'Uluslararası yarışmalar Türkiye’den ve dünyanın tüm ülkelerinden katılımcılara açıktır. Katılım sırasında T.C. kimlik numarası kontrolü veya başka bir kimlik doğrulaması yapılmaz.',
            ],
        ],

        'field_help' => [
            'audience' => [
                'description' => 'Yarışmanın ulaşacağı kitleyi seçin. Ulusal yarışmalarda yalnızca Türkçe, uluslararası yarışmalarda Türkçe ve İngilizce içerik istenir.',
                'example' => 'Türkiye dışından katılım kabul ediliyorsa “Uluslararası Yarışma” seçin.',
            ],
            'name' => [
                'description' => 'Yarışmanın duyuru, şartname ve sonuç ekranlarında kullanılacak açık ve resmî adını yazın.',
                'example' => 'TFSF 2026 Ulusal Doğa Fotoğraf Yarışması',
            ],
            'organizing_institution' => [
                'description' => 'Yarışmayı düzenleyen kurum, oturum açtığınız kurum hesabından otomatik alınır. Bu bilgi yarışma ekranından değiştirilemez.',
            ],
            'partners' => [
                'description' => 'Yarışmanın düzenlenmesine katkı sağlayan paydaş ve işbirlikçileri yazabilirsiniz. Alan opsiyoneldir; birden fazla kurum varsa virgülle ayırın.',
                'example' => 'Örnek Belediyesi, Örnek Fotoğraf Derneği, Örnek Üniversitesi',
            ],
            'subject' => [
                'description' => 'Yarışmada ele alınacak tema veya konuyu açık ve anlaşılır biçimde açıklayın. Bu alan zorunludur ve en fazla 1000 karakter olabilir.',
                'example' => 'Türkiye’nin doğal yaşamını, biyolojik çeşitliliğini ve koruma altındaki alanlarını belgeleyen fotoğraflar.',
            ],
            'purpose' => [
                'description' => 'Yarışmanın düzenlenme amacını, hedefini ve oluşturması beklenen etkiyi açıklayın. Bu alan zorunludur ve en fazla 1000 karakter olabilir.',
                'example' => 'Doğal yaşamın korunmasına yönelik toplumsal farkındalığı artırmak ve fotoğraf sanatını desteklemek.',
            ],
        ],

        'save_draft' => 'Taslak Olarak Kaydet',
        'next_step' => 'İleri',
        'draft_saved' => 'Taslak kaydedildi.',
        'coming_soon' => 'Bu adım yakında eklenecek.',
        'needs_info_title' => 'Ek bilgi talep edildi',
        'ready_to_submit_title' => 'Onaya göndermeye hazır',
        'ready_to_submit_hint' => 'Doldurduğunuz bilgiler onay için EYS\'ye gönderilecek.',
        'submit_for_approval' => 'Onaya Gönder',
        'submitted' => 'Başvurunuz onaya gönderildi.',
        'cannot_submit_incomplete' => 'Onaya göndermeden önce zorunlu alanları tamamlamanız gerekiyor.',
    ],

];
