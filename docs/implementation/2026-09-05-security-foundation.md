# Yeniden geliştirme: ilk güvenlik uygulama paketi

Tarih: 5 Eylül 2026. Dal: `codex/tfsf-security-foundation`. Başlangıç: `23a754e`.

Bu paket, eski sistemin tüm özelliklerini yeni uygulamada karşılama planının ilk teslimidir. Yerel kod ve otomatik doğrulama kapsamındadır; canlıya dağıtım, eski veri aktarımı veya bütün iş paketlerinin kabulü anlamına gelmez.

Bu belge ilk paketin kabul kanıtını korur. E-posta/aktivasyon devamı ve sonraki tam test sonuçları [hesap kurtarma paketinde](2026-09-05-account-recovery.md) kayıtlıdır.

## Uygulanan davranışlar

| Plan işi | Değişiklik | Kanıt / sınır |
|---|---|---|
| 01.01 | Üye girişinde ve mevcut oturumun her isteğinde güncel hesap durumu ve hesap kısıtı denetleniyor. Etkin olmayan hesap kapatılıyor; katılım kısıtı profil erişimini kapatmıyor. | `MemberAccessSecurityTest`: status 0/90, açık oturum, aktif/gelecek/süresi dolmuş/kaldırılmış kısıtlar. Diğer panellerin tüm yetki denetimi 01.03 kapsamında devam ediyor. |
| 01.02 — SMS bölümü | Kod kullanıcıya bağlanıyor; 10 dakika geçerli, en fazla 5 yanlış deneme. Yeni kod eskisini iptal ediyor. Parola değişimi ve kod tüketimi aynı işlemde, kullanıcı ve kod satırları kilitlenerek yapılıyor. Telefon başına 10 dakikada 3 gönderim; ayrıca IP sınırları var. | `SmsPasswordResetSecurityTest`, `SmsPasswordResetConcurrencyTest`. İki hesapta aynı telefon varsa işlem yapılmıyor. Bulunamayan telefon ve sağlayıcı başarısızlığında genel yanıt; sağlayıcı hatasında kod siliniyor. E-posta sıfırlama ve aktivasyon ayrıca tamamlanacak. |
| 01.06 | Jüri/kamu JPEG türevi metadata temizlenerek üretiliyor. Dönüşüm başarısızsa yükleme reddediliyor. Doğrulanmış türev yoksa fotoğraf uçları 404 dönüyor. | `CompetitionPhotoSecurityTest`: sentetik Artist/GPS verisi, orijinalin hash kontrolü, başarısız dönüşüm, jüri/kamu erişimi ve yeniden üretim. Piksele yazılmış imza/filigranı kaldırma veya tanıma bu pakette yok. |
| 10.03 — puan kartı bölümü | Resmî sonuç ve üye kartı aynı ağırlıklı hesap sorgusunu kullanıyor. SQLite tam sayı bölmesi giderildi; ara jüri ortalamaları yuvarlanarak genel ortalama hesaplanmıyor. | `WeightedScoreConsistencyTest`: eşit/eşitsiz/kesirli ağırlık, kesirli sonuç, eksik kriter ve yuvarlama. Eksik oyla tur kapatma politikası ve eski 3–9 toplam puan modu ayrı işler. |
| 13.03 | İmzalı webhook olay kimliğiyle tekilleşiyor. Geç gelen gecikme durumu teslimat sonucunu geriye götürmüyor; eski gönderim denemesi yeni denemenin durumunu değiştiremiyor. | `MailWebhookTest`: imza, tekrar, sıra, eski deneme ve gönderim kaydından önce ulaşmış olayın tekrarında bağlanması. Sağlayıcı tekrar göndermiyorsa bağımsız uzlaştırma işi henüz yok. |
| 01.05 — bağımlılık bölümü | CommonMark 2.9.0 → 2.10.0; nette/schema 1.3.5 → 1.3.6. Composer ve npm taramaları CI'a eklendi. | Yerel kilit dosyası taramaları bilinen açık bildirmedi. Eski sistem sırlarının yenilenmesi ve bütün kaynak/log taraması tamamlanmış sayılmaz. |
| 03.04 — posta/portfolyo ayarları | Tekil ayar kaydının `id=1` kimliği iç oluşturma yolunda korunuyor. Otomatik ID sayacı ilerlediğinde aynı ayar tekrar çağrılınca yeni kayıt oluşması giderildi. | `SingletonSettingsTest`: sayaç 42'yi geçtikten sonra oluşturma/güncelleme/yeniden okuma, iki ayar türü. Mevcut ayar ve portfolyo testleri MariaDB'de de geçti. |
| 15.02 / 15.04 — altyapı bölümü | CI SQLite ve MariaDB 11.8 matrisine alındı. SMS tüketimi ayrı PHP süreçleriyle MariaDB üzerinde sınanıyor. | Kota, jüri kesinleştirme, ödül, outbox, yük ve kesinti testleri sırada. GitHub CI henüz çalıştırılmadı. |

MariaDB doğrulaması ayrıca `2026_08_30_210000_add_public_catalogue_fields` migration'ının geri alınmasında InnoDB'nin yabancı anahtar için kullandığı birleşik indeksin kaldırılamadığını gösterdi. `down()` önce gereken tek sütun indeksini yeniden oluşturuyor; concurrency testinin migration geri alma adımı da geçiyor.

## Veritabanı ve mevcut dosyaların geçişi

Üç yeni migration:

1. `2026_09_05_100000_harden_sms_password_reset_codes`: nullable `user_id` ve `failed_attempts`. Önceden üretilmiş, kullanıcıya bağlı olmayan SMS kodları doğrulanmaz; kullanıcı yeni kod ister.
2. `2026_09_05_110000_mark_sanitized_competition_photo_copies`: nullable `jury_sanitized_at`. Eski kopyalar otomatik güvenilir kabul edilmez.
3. `2026_09_05_120000_track_mail_provider_status_time`: `mail_send_logs.provider_status_at`. Geçmiş webhook sırası uydurularak doldurulmaz.

**Bu migration'lar gerçek uygulama veritabanında çalıştırılmadı. Mevcut yarışma fotoğrafları yerinde dönüştürülmedi.**

Yayın öncesi işletim sırası:

1. DB ve private/public dosyaların birlikte geri yüklenebilir yedeğini al; staging kopyasında geçişi prova et. Dönüşüm sırasında ek dosyalar için yeterli disk alanını doğrula.
2. Geçiş süresince ilgili fotoğraf yükleme/görüntüleme ve arka plan yazmalarını kontrollü kapat. Yeni kodu ve bağımlılıkları yerleştir; yeni migration'ları uygula.
3. `php artisan photos:sanitize-competition-copies --dry-run` ile işlenecek kayıt sayısını al.
4. `php artisan photos:sanitize-competition-copies` çalıştır. Komut özel orijinalden yeni özel JPEG üretir ve ancak başarılı yazmadan sonra güven işaretini koyar. Hatalı kaydı fotoğraf kimliğiyle bildirir; diğer kayıtlara devam eder; hata varsa çıkış kodu sıfır değildir.
5. Hataları orijinal dosya/izin/depolama düzeyinde giderip aynı komutu tekrar çalıştır. Tamamlanan kayıtlar atlanır. Son `--dry-run` sayısı sıfır olmalı; orijinali olmayan kayıtlar ayrıca uzlaştırılmadan erişime açılmaz.
6. Önceden public alana kopyalanmış türevlerin manifestosunu çıkar; yalnız ilgili eski türevleri kontrollü kaldır. Eski HTTP/CDN önbelleğini temizle. Yeni uçlar private kopyayı kullansa da geçmiş public URL veya önbellek tek başına DB işaretiyle iptal olmaz. Komut eski kopyaları kendiliğinden silmez.
7. Örnek orijinallerin hash'ini, yeni türevlerde metadata yokluğunu, jüri/kamu ve yetkisiz erişimi doğrula; ardından ilgili trafiği aç.

Şema geri alınabiliyor olması eski güvensiz fotoğraf sunumuna dönmeyi uygun kılmaz. Geri dönüşte trafiği kapalı tutup DB/dosyaları aynı yedek noktasından ele al; bu işletim provası henüz yapılmadı.

## Yerel doğrulama

PHP 8.5.10 konteyneri, Imagick ve ExifTool kullanıldı. CI PHP 8.4 hedefliyor; o sürümdeki uzak koşu henüz yok. SQLite testleri bellek içinde; MariaDB testleri gerçek uygulama DB'sinden ayrı, yalnız sentetik veri içeren `tfsf_testing` veritabanında çalıştırıldı. Gerçek e-posta/SMS gönderilmedi.

| Kontrol | Sonuç |
|---|---|
| Tam SQLite testi | 513 geçti, 1 atlandı; 1.909 assertion. Atlanan test yalnız MariaDB için iki süreçli SMS testidir. |
| Tam MariaDB testi | 514 geçti; 1.918 assertion; atlanan test yok. |
| İki süreçli SMS testi, MariaDB | 1 geçti; 9 assertion. Tek kod iki eşzamanlı istekte yalnız bir kez tüketildi. |
| Laravel Pint | 528 PHP dosyası geçti. |
| `npm run build` | Geçti. |
| `composer audit --locked` | 0 advisory, 0 abandoned paket. |
| `npm audit --audit-level=high` | Toplam 0 bildirilen açık. |
| CI YAML ve diff | YAML ayrıştırma/matris kontrolü ve `git diff --check` geçti. |

Önceki inceleme tabanı 478 testti; bu paket toplam 36 yeni senaryo ekledi. Kaydedilmiş terminal sonuçları `evidence/security-foundation-checks.txt` içindedir. Bunlar yerel koşu kanıtıdır; uzak CI ve pilot kabul bekliyor.

Testleri çalıştırma:

```sh
php artisan test --compact
vendor/bin/pint --test
npm run build
composer audit --locked
npm audit --audit-level=high
```

MariaDB testi için CI'daki izole `tfsf_testing` ayarlarını kullan. Testler tabloları yeniden kurar; uygulama veya paylaşılan geliştirme veritabanında çalıştırılmaz. İki süreçli test özellikle `testing` ortamını ve `tfsf_testing` adını denetler. SQLite ve MariaDB test paketlerini aynı checkout üzerinde eşzamanlı çalıştırma: sahte storage dizinlerini paylaşırlar.

## Sıradaki işler

İlk sıra: PK-00 alan/işlem sözlüğünün ayrıntılandırılması; 01.02 e-posta/aktivasyon; 01.03–01.04 panel ve nesne yetkileri/HTTP güvenliği; 06.05 atomik kota ve başvuru revizyonu; 13.02 bildirim işlerinin atomik sahiplenilmesi. Ardından ön kayıt/belgeler, dernekler, yarışma serileri, kategori turları, temsilci ödül/tutanakları, tarihsel sonuçlar, CMS/şikâyet ve veri aktarımı plan bağımlılıklarıyla ilerleyecek.

Hiçbir eski özellik kapsamdan çıkarılmadı. Bu paketteki otomatik test sonuçları, bütün plan için ürün sahibi/pilot kabulü yerine geçmez.
