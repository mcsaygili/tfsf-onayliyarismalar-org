# E-posta parola sıfırlama ve hesap doğrulama paketi

5 Eylül 2026. Dal: `codex/tfsf-security-foundation`. PK-01.02'nin e-posta/aktivasyon alt kapsamı; ilk güvenlik paketinin devamı.

Sonraki açık oturum ve kullanıcı yönetimi çalışması [üçüncü pakette](2026-09-05-panel-sessions-and-permissions.md) kayıtlıdır. Bu belge ikinci paketin test kanıtını korur.

## Kullanıcıya yansıyan değişiklikler

Üye, kurum, temsilci, jüri ve EYS panellerinin e-posta sıfırlama isteği artık adresin kayıtlı olup olmadığını hata mesajıyla açıklamıyor. Gönderim sınırına ulaşılması ve sağlayıcı hatası da aynı genel yanıtı kullanıyor. Geçersiz token ve bilinmeyen adres ile parola değiştirme denemeleri aynı hata mesajını alıyor. Bu davranış kayıt ekranındaki mükerrer e-posta kontrolünü değiştirmez.

Her panelin mevcut ayrı token tablosu korundu. Token kaydı artık hesabın UUID'sine bağlı: adres başka hesaba aktarılırsa eski token yeni hesaba uygulanamıyor. Süre sonu dahil, 60 dakikasını dolduran bağlantı reddediliyor. Yeniden üretilen token eskisini geçersiz kılıyor; başarılı kullanım parola ve remember token değişikliğiyle birlikte atomik tüketiliyor. Token hash'li saklanmaya devam ediyor.

Gönderim uçlarında IP başına dakikada 10 istek; parola değiştirme uçlarında dakikada 30 istek sınırı var. Gönderimde ayrıca panel+adres başına 10 dakikada 3 istek bütçesi ve broker'ın 60 saniyelik token oluşturma aralığı uygulanıyor. Adres bütçesi IP değiştirerek aşılamıyor. Sunucu tarafında e-posta/token türü ve uzunluğu doğrulanıyor. Genel yanıt, teslimatın gerçekleştiğini garanti eden bir bildirim değildir.

Üye, kurum, temsilci ve jüri doğrulama uçları mevcut imzalı/süreli bağlantıları koruyor. Yetkilendirmeden sonra hesap kaydı kilitleniyor ve e-posta hash'i tekrar kontrol ediliyor. Arada adres değiştirilmişse işlem reddediliyor. Tekrar kullanım ilk doğrulama zamanını değiştirmiyor; iki eşzamanlı istek tek `Verified` olayı üretiyor. EYS'de bu dört paneldeki gibi bir kendi kendine e-posta doğrulama akışı eklenmedi.

## İşlem güvenceleri

- `App\Auth\PasswordBrokerManager`, beş mevcut broker için kullanıcıya bağlı token deposu ve atomik broker oluşturuyor. Laravel'in facade/broker ve panele özel bildirim arayüzleri korunuyor.
- Kilit sırası hesap → token. Token kontrolü de kilitli sorgu kullanıyor; MariaDB'nin repeatable-read görünümünden eski tokenı okuyup yeniden tüketme riski önleniyor.
- Parola güncelleme başarısızsa transaction geri alınıyor ve token tüketilmiyor. `PasswordReset` ve `Verified` olayları commit sonrasında yayımlanıyor.
- Token oluşturma ve gönderim aralığı kontrolü aynı hesap kilidi altında. Dış e-posta gönderimi DB transaction'ı kapandıktan sonra yapılıyor.
- Bulunamayan hesap ve geçersiz token için broker süre koruması korunuyor. Bu, gerçek ağ/sağlayıcı sürelerinin tamamen eşitlendiği iddiası değildir.
- Sağlayıcı hatası loguna yalnız broker adı ve exception sınıfı yazılıyor; exception mesajı, adres veya token eklenmiyor. Sağlayıcı hatası durumunda üretilmiş token süre sonuna kadar kalabilir; yeni istek mevcut hız sınırlarına uyar. Teslimat tekrarlarının outbox üzerinden yönetilmesi PK-13.02 kapsamındadır.

## Geçiş

Yeni migration: `2026_09_05_130000_bind_email_password_reset_tokens_to_accounts`.

Beş mevcut token tablosuna nullable `user_id` ve ilgili hesap tablosuna yabancı anahtar ekler. Eski kayıtların sahipliği e-posta üzerinden tahmin edilerek doldurulmaz: `user_id` boş tokenlar reddedilir, kullanıcı yeni bağlantı ister. Hesap silinirse ilgili token da silinir.

Bu migration gerçek uygulama DB'sinde çalıştırılmadı. Staging'de yedekli geçiş provasında önceki paketin üç migration'ıyla birlikte uygulanmalı. Uygulama kodu ve şema aynı yayın adımında etkinleştirilmeli; eski bağlantıların yenilenmesi gerektiği destek ekibine bildirilmeli. Kullanıcı parolaları ve hesap doğrulama zamanları migration ile değiştirilmez.

Bu broker, mevcut projedeki Eloquent hesap modelleri ve aynı uygulama veritabanındaki token tabloları için tasarlandı. Cache token sürücüsü desteklenmez; hesap ve token bağlantılarını ayrı veritabanlarına taşımak bu transaction güvencesiyle uyumlu değildir ve ayrıca tasarlanmalıdır.

## Doğrulama

Yeni test dosyaları:

| Dosya | Kapsam |
|---|---|
| `EmailPasswordResetSecurityTest` | Beş panelde genel yanıt, bağlı token, tek kullanım, adres devri, süre sınırı, eski/yenilenen token, adres bütçesi; panel geçişi, transaction hatası, sağlayıcı log gizliliği |
| `EmailVerificationSecurityTest` | Dört panelde imza/süre, değiştirilmiş URL, başka hesap, eski e-posta hash'i, tekrar kullanım ve IP'den bağımsız doğrulama gönderim sınırı |
| `EmailAccountConcurrencyTest` | Beş panelde iki ayrı HTTP sürecinin aynı tokenı tüketmesi; dört panelde aynı doğrulama bağlantısının eşzamanlı kullanılması; tek olay doğrulaması |

İki süreçli 9 yeni MariaDB senaryosu geçti (91 assertion). Toplam yeni senaryo sayısı 58: 49 genel test ve 9 MariaDB süreç testi. İlk paketteki SMS süreç testiyle birlikte SQLite'ta 10 test tasarım gereği atlanır; bu testlerin kabul motoru MariaDB'dir.

| Kontrol | Sonuç |
|---|---|
| Tam MariaDB 11.8 paketi | 572 geçti; 2.344 assertion; atlanan test yok. |
| Tam SQLite paketi | 562 geçti; 2.244 assertion; yalnız MariaDB'ye özel 10 süreç testi atlandı. |
| Laravel Pint | 539 PHP dosyası geçti. |
| `git diff --check` | Geçti. |

Koşular yerel PHP 8.5.10 konteynerinde yapıldı; MariaDB tamamen ayrı `tfsf_testing` veritabanıydı. Uzak CI/PHP 8.4 koşusu ve pilot kabul yapılmadı. Ön yüz ve bağımlılık dosyaları bu ikinci pakette değişmedi; derleme/audit kanıtı ilk pakette bulunuyor.

Terminal kanıtı: `evidence/account-recovery-checks.txt`. Test konteyneri doğrulama sonrasında kaldırıldı; uygulama DB'si değiştirilmedi.

Henüz tamamlanmayan kapsam: bütün panellerde açık oturum ve nesne yetkisi denetimleri (PK-01.03), tüm HTTP/CSRF ve içerik güvenliği (PK-01.04), parola sıfırlama sonrası mevcut bütün oturumların iptali için ortak politika, bildirim outbox/retry güvenceleri (PK-13.02), gerçek ortam geçiş/pilot kabulü. Bu teslim bu işlerin tamamlandığını iddia etmez.
