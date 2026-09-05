# Panel oturumları ve EYS kullanıcı yetkileri

5 Eylül 2026. Dal: `codex/tfsf-security-foundation`. PK-01.03 / PK-02.03–02.04 alt kapsamı. Önceki iki güvenlik paketinin devamı; bütün rol ve nesne yetkilerinin kabulü değildir.

## Beş panelde ortak oturum kontrolü

`EnsurePanelSession`, üye, kurum, temsilci, jüri ve EYS panel gruplarında bakım kontrolünden sonra, normal auth/yetki kontrollerinden önce çalışıyor. Her istekte hesabı sağlayıcısından yeniden okuyor. Böylece pasiflik, kuruma bağlılık ve EYS izinleri önceki istekte yüklenmiş model ilişkilerinden okunmuyor.

Üye durumları ve hesap/katılım kısıtları mevcut `MemberAccessPolicy` kurallarını kullanıyor. Diğer dört panelde pasif hesap reddediliyor. Kurum personeli için hem personel hem bağlı kurum etkin olmalı. Kullanıcının hesabı kaybolmuşsa da erişim kapanıyor. Reddedilen JSON isteği 403, tarayıcı isteği kendi panelinin giriş sayfasına yönlendirme alıyor.

Gerçek `Login` olayı, oturuma panel + hesap kimliği + e-posta + parola hash'ine bağlı bir HMAC doğrulaması yazıyor. Parola hash'i doğrudan oturuma yazılmıyor. Sonraki isteğin güncel hesap kaydı bu doğrulamayla uyuşmalı. Parola başka cihazda, SMS/e-posta sıfırlamada veya yönetim üzerinden değişirse eski oturum sonraki istekte reddedilir. E-posta değişikliği de eski oturum doğrulamasını geçersiz kılar.

Hatırlama çereziyle yeniden girişte framework'ün parola hash'i/HMAC alanı ayrıca kontrol ediliyor; eski çerez yeni bir oturum kanıtı alarak parola değişikliğini atlayamıyor. Eski tek bir oturumun reddedilmesi, başka cihazın güncel remember tokenını yeniden döndürmüyor.

Üye, kurum, temsilci ve jüri kendi parolasını değiştirirken mevcut parola kilitli güncel kayıtla yeniden doğrulanıyor. Parola ve remember token değişiyor, mevcut oturum kimliği yenileniyor ve yalnız bu oturumun kanıtı güncelleniyor. Diğer oturumların kanıtları eski kalıyor. EYS için yeni bir kendi kendine parola ekranı eklenmedi; mevcut e-posta sıfırlama akışı kullanılıyor.

## EYS kullanıcı yönetimi

Önceki davranışta her aktif EYS hesabı başka bir EYS hesabının e-posta adresini veya durumunu değiştirebiliyordu. Böyle bir adres değişikliği parola sıfırlamasıyla hesap ele geçirmeye dönüşebilirdi. İşlemler mevcut `eys.users.*` izinlerine bağlandı:

| İzin / işlem | Yetki |
|---|---|
| `eys.users.view` veya `eys.users.manage` | Kullanıcı listesini görme |
| `eys.users.create` veya `eys.users.manage` | EYS hesabı oluşturma; rol atama bu izne dahil değil |
| `eys.users.edit` veya `eys.users.manage` | Başka hesabın ad/soyad/telefon profilini düzenleme |
| `eys.users.manage` | Başka hesabın e-posta adresi ve hesap durumunu değiştirme |
| Kendi hesabı | İsim/telefon profilini düzenleme; kendi e-posta değişikliği mevcut parola doğrulaması ister |
| `eys.roles.manage` | Mevcut ayrı rol atama yetkisi; kullanıcı düzenlemek kendiliğinden rol atama hakkı vermez |

Bu izinler Eys modülündeki atamalarla geçerli. Aynı global izin başka modülde verilmişse EYS kullanıcı yönetimine taşınmıyor. Kendi profil erişimi kullanıcı listesi iznine bağımlı değil; yetkisiz durum alanı gönderimi reddediliyor. Başka kullanıcının e-posta adresini profil editörü izniyle değiştirme engelleniyor.

Menü, yeni kullanıcı düğmesi, profil/rol işlem düğmeleri ve hesap durumu alanı sunucudaki izinlere uyuyor. Kişinin kendi e-posta değişikliğinde parola alanı gösteriliyor. E-posta değiştiğinde EYS remember tokenı yenileniyor. Kendi adresini mevcut parolasıyla doğrulayarak değiştiren kişinin mevcut oturumu korunuyor.

E-posta ve mevcut parola kontrolleri kilitli güncel kayıt üzerinde tekrarlanıyor. Rota kaydı çözümlendikten sonra başka bir işlem adresi değiştirmişse sıradan profil editörü eski formdaki adresi geri yazamıyor. Kişinin parolası ilk HTTP kontrolünden sonra değişmişse eski parola adres değiştirmek için kullanılamıyor. Bu iki zaman aralığı deterministik regresyon testleriyle ayrıca denetlendi.

## Dağıtım etkisi

Bu üçüncü pakette yeni migration yok. Önceki paketlerin dört migration'ı yine gerekli; gerçek uygulama veritabanında çalıştırılmadılar.

**Oturum kanıtı bulunmayan mevcut girişler yeni kodla ilk istekte yeniden girişe yönlenir.** Eski oturuma güncel paroladan sessizce yeni kanıt verilmez. Geçerli hatırlama çerezi ayrı parola kontrolüyle yeni oturum açabilir. Dağıtım duyurusunda yeniden giriş gereksinimini belirt.

Dağıtımdan önce yetkili işletim hesabının Eys modülünde `eys.users.manage` ve gerekli rol yönetimi izinlerine sahip olduğunu doğrula. İzin adları zaten kayıt defterinde bulunuyor; bu paket herkese yönetici izni atayan bir veri güncellemesi içermiyor. Mevcut seed komutlarını bilinçsizce tekrar çalıştırmak özel rol izinlerini eşitleyebileceği için öncelikle mevcut atamaları incele.

## Test kapsamı ve sınırlar

`PanelSessionSecurityTest` gerçek giriş isteklerini, açık hesap pasifliğini, dış parola değişikliğini, kanıtsız eski oturumu, geçerli/eski hatırlama çerezlerini, pasif kurumu, kaldırılan EYS iznini ve kendi parola değişikliğini sınar. `UserAuthorizationTest` yetkisiz liste/oluşturma/düzenleme, kendi profil ve e-posta değişikliği, durum manipülasyonu, profil editörünün sınırı, görünür işlemler ve modül ayrımını sınar.

Test altyapısındaki `actingAs` gerçek Login olayını atladığı için test başlangıcında aynı oturum kanıtını oluşturacak şekilde uyarlandı. Bu işlem middleware'i kapatmıyor veya her istekte kanıtı yenilemiyor; yeni testlerin gerçek giriş yolu ayrıca doğrulanıyor. E-posta doğrulama süreç worker'ı da yalnız izole test hesabına aynı başlangıç kanıtını koyuyor.

Bu paket 45 yeni senaryo ekledi. Tarayıcıyla görsel/pilot kabul henüz yapılmadı; Blade erişim koşulları HTTP testleriyle denetlendi.

| Kontrol | Sonuç |
|---|---|
| Tam SQLite paketi | 607 geçti, 2.497 assertion; yalnız MariaDB'ye özel 10 süreç testi atlandı. |
| Tam MariaDB 11.8 paketi | 617 geçti, 2.597 assertion; atlanan test yok. |
| Laravel Pint | 545 PHP dosyası geçti. |
| `npm run build` | Geçti. |
| `git diff --check` | Geçti. |

Koşular yerel PHP 8.5.10 konteynerinde, SQLite bellekte ve ayrı `tfsf_testing` MariaDB veritabanında yapıldı. Uzak CI/PHP 8.4 koşusu ve gerçek ortam geçişi henüz yapılmadı. Yeni gerçek SMS/e-posta gönderilmedi.

Terminal kanıtları `evidence/panel-sessions-and-permissions-checks.txt` dosyasında. İzole MariaDB test konteyneri doğrulama sonrasında kaldırıldı.

Açık işler: bütün yarışma/kategori/kurum uçlarının nesne yetkisi envanteri, kurum personeli/sekreterya rol ayrımı, yönetici kritik işlemlerinde ortak yeniden doğrulama ve MFA, kalıcı oturum iptal sürümü (ör. pasifleştirildikten sonra hiç istek yapmadan yeniden etkinleştirilen hesap), bütün bekleyen kurtarma tokenlarının başka bir kanaldan parola değişiminde iptali, kapsamlı denetim geçmişi ve gerçek ortam/pilot kabulü. Bu paket mevcut istek başındaki erişimi denetler; daha önce yetkilendirilip çalışmaya başlamış bütün işlerin anında iptal edildiğini iddia etmez.
