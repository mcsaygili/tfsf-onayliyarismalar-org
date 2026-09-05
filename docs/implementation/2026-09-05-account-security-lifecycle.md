# Hesap güvenlik durumu, oturum ve kurtarma yaşam döngüsü

5 Eylül 2026 — PK-01.01/01.02/01.03 ve PK-02.03 alt kapsamı. Dal: `codex/tfsf-security-foundation`. Yerel uygulama; üretim geçişi ve pilot kabul açık.

## Sorun ve yeni davranış

Önceki oturum kanıtı kullanıcı kimliği, e-posta ve parola hash'ine bağlıydı. Hesap kapatılıp yeniden açılırsa ve cihaz arada istek yapmazsa eski kanıt yeniden geçerli olabiliyordu. SMS ile e-posta parola sıfırlama talepleri de bağımsızdı; bir kanaldan parola değiştikten sonra diğerinde bekleyen geçerli talep yeni parolayı tekrar değiştirebiliyordu.

Beş oturum açan model ve kurum kaydı kalıcı bir `security_stamp` alanı kazandı. Hesaplarda parola, e-posta, telefon, aktiflik ve kurum bağlantısı değiştiğinde damga aynı SQL güncellemesinde rastgele yenilenir. Kurumun aktiflik değişikliği kendi damgasını yeniler. E-posta veya telefon eski değerine döndürülse ve hesap yeniden açılsa bile eski damga geri gelmez. Ad/soyad gibi sıradan profil alanları bu değişimi tetiklemez.

`AccountSecurityContext` damgayı, kimliği ve güncel kimlik bilgilerini HMAC ile bağlar. Kurum personelinde kurumun güncel damgası da denetlenir. Alanlar doldurulabilir kullanıcı alanları değildir ve model JSON çıktılarında gizlidir.

### Oturumlar ve beni hatırla

- `PanelSession` mevcut beş panelin oturum imzasına güvenlik bağlamını ekler. Kapat/aç ve e-posta değiştir/geri al döngüsü eski oturumu canlandırmaz.
- Yeni `BoundAccountProvider`, hatırlama çerezinin sunucuda kayıtlı `remember_context` değerini denetler. Kurum yeniden açıldığında personelin eski çerezi de reddedilir.
- Kimlik değişiminde hesapta eski hatırlama tokenı/bağlamı temizlenir. Geçerli parolayla yeni giriş yapan kullanıcı, kurum kaynaklı iptalden sonra da yeniden “beni hatırla” kullanabilir.
- Geçersiz bağlamla sadece kullanıcı aramak veritabanına yazmaz. Başarılı hatırlamalı girişte framework yeni tokenı ve bağlamını kaydeder.
- Mevcut öz hizmet parola değişikliği, parolayı kilit altında kontrol ederek yalnız değişikliği yapan oturumun yeni kanıtını üretmeye devam eder.

### Üye engelleri

Hesap türündeki engelin oluşturulması, zaman/hesap/tür/kaldırma bilgilerinin değiştirilmesi veya silinmesi ilgili üyenin damgasını ve hatırlama tokenını aynı transaction içinde iptal eder. Geleceğe planlanan bir hesap engelini kaydetmek de mevcut cihazlarda yeniden giriş gerektirebilir; engel başlamadan doğru parolayla yeni giriş yasaklanmaz. Yalnız gerekçe metnini değiştirmek iptal nedeni değildir. Katılım türündeki engeller oturum iptal etmez.

Başlama zamanı gelmiş hesap engellerinin kimlikleri, engel doğal süresiyle bitse de güvenlik bağlamında kalır. Böylece engel başlamadan alınmış oturum/hatırlama/kurtarma kanıtı, cihaz arada hiç bağlanmamış olsa bile bitişten sonra geri geçerli olmaz. Başlamadan kaldırılmış engeller bu zaman denetimine girmez. Başlamış bir engel kaldırılır veya silinirse kalıcı damga değişimi eski kanıtın geri gelmesini önler. Transaction geri alınırsa engel ve iptal birlikte geri alınır. Değişmiş Eloquent nesnesi bir veritabanı hatasından sonra otomatik yeniden kullanılmaz; işlem hata verir ve geri alınır. Engel SQL yazması ile hesap iptali arasına eklenen hata enjeksiyonu testi, yanlış başarı veya yarım kayıt kalmadığını doğrular.

### E-posta ve SMS kurtarma

Beş e-posta token tablosu ve SMS kod tablosunda `security_context` bulunur. Talep oluşturulurken kilitli güncel hesaptan üretilir; tüketilirken güncel bağlamla karşılaştırılır. Önceki UUID/süre/hash/deneme sınırı kontrolleri korunur. Bağlamı olmayan eski talepler reddedilir. Eski bağlamdaki bir e-posta kaydı yeni talebi gereksiz yere süre sınırına takmaz.

Bir parola değişince diğer kanalda bekleyen talepler geçersiz olur. Eski kaydın fiziksel olarak bulunması kullanım hakkı vermez; olağan süre sonu temizliği ayrıca uygulanmalıdır. Yeni bir kurtarma isteği yeni bağlamla kullanılabilir. Aktiflik değişikliği, eski e-posta/telefona dönüş, kurum kapanıp açılması ve üye hesabı engeli de eski talepleri etkisiz kılar.

Transaction içinde güvenlik bağlamının kurum/engel okumaları kilitli okuma kullanır; MariaDB REPEATABLE READ altında eski ilişki görünümünün yanlış kabul üretmesi önlenir. Hesap kilidi önce alınır. İki farklı kanaldan eşzamanlı sıfırlama ve öz hizmet parola değişikliği ile sıfırlamanın yarışı bağımsız PHP süreçlerinde sınandı.

## Doğrulama

38 yeni senaryo eklendi. Son kod üzerinde tam regresyon sonuçları:

| Kontrol | Sonuç |
|---|---|
| SQLite | 650 geçti, MariaDB'ye özel 14 test atlandı; 2.682 assertion |
| İzole MariaDB 11.8 | 664 geçti; 2.827 assertion |
| Pint | 557 PHP dosyası geçti |
| `git diff --check` | Geçti |

PHP 8.5 test konteyneri, SQLite bellek veritabanı ve ayrı `tfsf_testing` MariaDB veritabanı kullanıldı. Canlı uygulama veritabanına migration uygulanmadı. Test veritabanı konteyneri koşuların ardından kapatıldı.

Yeni senaryolar `AccountSecurityLifecycleTest` ve `AccountSecurityConcurrencyTest` içindedir. Mevcut hatırlama testlerinin geçerli çerez örnekleri gerçek provider üzerinden bağlandı. SMS test verileri yeni bağlam alanını içerir; eski bağlamsız kodların reddi testleri korundu. Bir mevcut üye erişim testi, kısıtlama öncesi yüklenmiş nesneyle yapay oturum yerine gerçek giriş isteğini sınayacak şekilde güçlendirildi.

MariaDB odaklı koşuda yeni testlerle birlikte önceki e-posta doğrulama/parola tüketimi ve SMS süreç testleri çalıştı: **45 geçti / 243 assertion**. Son üç MariaDB hedef testi de **3 geçti / 27 assertion** sonucunu verdi; buna engel/iptal arasındaki hata enjeksiyonu dahildir. İki farklı kurtarma kanalının yarışında tek parola değişimi ve tek `PasswordReset` olayı; öz hizmet ile e-posta yarışı için yalnız bir başarılı değişiklik doğrulandı.

[Ham doğrulama kanıtı](2026-09-05-account-security-lifecycle-evidence.txt)

## Dağıtım ve geri dönüş

1. `2026_09_05_150000_bind_sessions_and_recovery_to_security_state.php` migration'ı uygulama koduyla birlikte staging'de prova edilmeli. Gerçek veride bu çalışma sırasında çalıştırılmadı.
2. Geçiş yeni oturum kanıtı gerektirir; mevcut oturumlar yeniden giriş ister. Migration eski hatırlama tokenlarını temizler. Eski SMS/e-posta talepleri bağlamsız kalır ve yenilenmelidir. Hesaplar veya iş verileri silinmez.
3. Yeni kod/migration dağıtımı sırasında trafik ve worker geçiş sırası kontrol edilmeli. `config/auth.php` provider değişimi nedeniyle yapılandırma önbelleği yeni sürüm için yeniden üretilmeli.
4. Migration geri alınırken bekleyen kurtarma kayıtları ve hatırlama tokenları temizlenir. Eski kodun eski oturum imzasını yeniden kabul etmemesi için uygulamanın bütün sunucularındaki session store da uygulamaya özel kapsamda temizlenmeli. Yalnız veritabanı migration geri alımı, file/Redis gibi haricî session store'ları temizlemez. Bu işlem gerçek ortamda ayrıca prova edilmeli.
5. Yeni güvenlik alanlarıyla eski uygulama kodunun karışık çalışması desteklenmez; yedek, geri dönüş ve kontrollü kesim PK-14 kapsamında kalır.

## Sınırlar ve sıradaki işler

- Model olaylarını atlayan doğrudan SQL, `query()->update()`, `saveQuietly()` ve toplu importlar otomatik damga yenilemez. Uygulama kaynaklarında bu paket kapsamındaki aktiflik/kısıtlama yazmalarının model üzerinden yapıldığı tarandı. Gelecek veri aktarımı/toplu yönetim araçları damga/hatırlama iptalini açıkça uygulamalı; SQL ile kapat/aç döngüsünün güvenli olduğu varsayılmamalı.
- Tüm nesne/rol yetkileri ve işlemlerin ortasında yetki iptali hâlâ ayrı iş paketleri. Bu çalışma yalnız giriş kanıtı ve kurtarma yaşam döngüsünü kapatır.
- Profil e-postası değişiminde mevcut parola isteme, yeni adresin yeniden doğrulanması ve bekleyen imzalı doğrulama bağlantılarının bütün panellerde ortak politikası ayrıca tamamlanmalı. E-posta parola sıfırlama tokenı ile imzalı e-posta doğrulama bağlantısı aynı mekanizma değildir.
- Gerçek session sürücüsüyle çok sunuculu pilot, gerçek veri migration/rollback provası, yoğunluk ve zamanlı engel hacmi ölçümleri açık. Tam plan veya üretime hazır olma kabulü verilmedi.
