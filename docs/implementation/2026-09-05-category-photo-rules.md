# Kategoriye bağlı fotoğraf koşulları — yedinci uygulama paketi

5 Eylül 2026 · PK-06.02'nin fotoğraf alt kapsamı yerelde uygulandı ve doğrulandı. Ek dosyalar, eski veri aktarımı ve pilot kabul açık. Tüm PK-06 veya yeniden geliştirme planı tamamlanmış sayılmıyor.

## Değişen davranış

Kurum yarışma sihirbazının 6. adımında kategoriye göre JPEG/PNG/WebP seçimi, dosya boyutu alt/üst sınırı, kısa kenar alt sınırı, uzun kenar üst sınırı ve DPI alt/üst sınırı kaydediliyor. Kategori çoğaltma bu koşulları bağımsız kopyalar. Kaydetme hatasında değerler korunur. Türkçe/İngilizce özet; herkese açık kategori, üye yarışma detayı ve katılım ekranında gösterilir. Dosya seçicinin format ipucu kategoriye uyar; asıl denetim sunucudadır.

Doğrudan yükleme, portföyden ekleme ve geri çekilen eseri yeniden ekleme aynı doğrulayıcıdan geçer. Gerçek byte uzunluğu ve görsel başlığındaki format/ölçüler esas alınır; portföy satırındaki MIME/ölçü ya da dosya adı güven kaynağı değildir. Orijinal dosyanın saklama uzantısı gerçek formattan üretilir. Önceki anonim JPEG üretimi devam eder; bozuk görselin işlenememesi hâlinde orijinal jüri kopyası olarak kullanılmaz.

Kabul edilen fotoğrafın `eligibility_snapshot.technical` alanı kategori kurallarını, gerçek byte/MIME/ölçüleri ve denetlendiğinde DPI değerini/kaynağını tutar. Yeniden eklemede teknik snapshot yenilenir. Bu, tüm aktörlerin ortak değişmez başvuru/şartname sürümü protokolünün yerine geçmez.

## Kurallar ve eski sistem eşlemesi

| Eski kategori alanı | Yeni `photo_rules` alanı | Anlam |
|---|---|---|
| `min_file_size` / `max_file_size` | `min_file_size_mb` / `max_file_size_mb` | MiB; 1 MiB = 1.048.576 byte. Form üç ondalığa kadar kabul eder. |
| `short_edge` | `min_short_edge` | En kısa kenarın alt sınırı; portre/yatay/kare için aynı anlam. |
| `long_edge` | `max_long_edge` | En uzun kenarın üst sınırı. |
| `min_dpi` / `max_dpi` | Aynı isimler | İki eksenin her biri bütün etkin sınırları karşılar. |
| Eski yükleme akışındaki `jpg,jpeg` | `formats: ["jpeg"]` | Eski JPEG kategorisi için aktarım eşlemesi. Yeni mevcut kategorilerin varsayılanı JPEG/PNG/WebP olarak korunur. |

`null`, boş veya 0 sınırı devre dışı bırakır; alt/üst sınırlar bağımsızdır ve eşitlik kabul edilir. Negatif değer, ters aralık, kesirli piksel/DPI, boş veya desteklenmeyen format seçimi ve tanımsız JSON anahtarı taslak kayıtta da reddedilir. Teknik koşulları hiç göndermeyen eski form/API payload'ı, mevcut kategori kurallarını sıfırlamaz. Başka kuruma ait kategori/yarışma yetkisi mevcut politikayla denetlenir.

Eski kaynak: `ThirdParty/Administrator/Models/Yarisma/YarismaKategoriModel.php` ve `ThirdParty/Frontend/Views/Yarisma/yarismaKayitView.php:632–695`. Tarayıcı kodu çiftlerin ancak iki değeri de pozitifse kontrol yapıyordu. DPI kontrolünde yalnız X alt sınırı ve Y üst sınırı uygulanıyordu. Yeni kod bu hatayı korumaz. Eski tek taraflı/dengesiz değerler aktarım öncesi kalite raporunda görünür olmalıdır. Legacy Plupload `parseSizeStr` kodunda `m:1048576` olduğu doğrulandı.

## DPI okuma politikası

DPI yalnız bir DPI sınırı etkinse istenir. Kural yokken eksik EXIF/DPI fotoğrafı engellemez. Kural varken ölçü birimi olmayan, sıfır/eksik eksenli veri reddedilir; ExifTool çalışmaz/zaman aşımına uğrarsa koşul sessizce atlanmaz ve yeniden deneme mesajı gösterilir.

Öncelik orijinal görselin `IFD0` EXIF çözünürlüğündedir; bulunmadığında JFIF veya PNG-pHYs kullanılır. IFD1 küçük resim ve XMP açıklamaları kullanılmaz. Birincil EXIF çözünürlüğü bulunup geçersizse başka gruptaki uygun değerle aşılmaz. JFIF'in EXIF'ten farklı birim numaraları ayrı yorumlanır. Santimetre başına değer 2,54 ile, metre başına değer 0,0254 ile DPI'a çevrilir. PNG'nin tam sayı piksel/metre gösterimi için sonuç iki ondalığa yuvarlanır; 11811 px/m böylece 300 DPI olur. DPI baskı yoğunluğu metadatasıdır; piksel çözünürlüğü ayrıca denetlenir.

Birimlerin kaynakları: [ExifTool JFIF etiketleri](https://exiftool.org/TagNames/JFIF.html), [ExifTool PNG fiziksel piksel etiketleri](https://exiftool.org/TagNames/PNG.html). EXIF/IFD0 santimetre ve inç durumları gerçek ExifTool çıktısıyla test edildi.

## İşletim ve geçiş

- Migration: `2026_09_05_160000_add_category_photo_rules.php`; nullable JSON sütunu ekler, mevcut kategori verilerini yeniden yazmaz. Eski kategorilerin varsayılanları korunur. Gerçek veritabanında çalıştırılmadı.
- `config/competition-photos.php`: `COMPETITION_PHOTO_MAX_MB` varsayılan 15 MiB; form, HTTP yüklemesi ve portföyden kabul aynı sınırı uygular. `COMPETITION_PHOTO_MAX_PIXELS` varsayılan 100.000.000 pikseldir ve görüntü çözülmeden başlık üzerinden denetlenir. Eski portföy kopyalama yolu byte sınırı uygulamıyordu; artık uygulanır.
- Daha büyük eski dosyaları desteklemek için limitler, PHP `upload_max_filesize` / `post_max_size`, proxy sınırları ve Imagick kaynak bütçesi birlikte hazırlanmalı, hacim testinden geçirilmelidir. Kaynak veri görülmeden büyük dosyalar sessizce kesilmez veya yeniden boyutlandırılmaz. Mevcut 15 MiB sınırı tam eski veri uygunluğu kanıtı değildir.
- ExifTool ve Imagick dağıtım gereksinimidir. Okuyucu shell metni kurmaz; argüman dizisi ve stdin kullanır, ExifTool süresi 5 saniyeyle sınırlıdır. Byte/piksel ön kontrolü bir medya işleme sandbox'ı veya tam kaynak tüketimi testi yerine geçmez.
- Rollback JSON sütununu ve kaydedilmiş koşulları kaldırır. Önce koşulların yedeği ve geri dönüş kararı gerekir; eski sürüme dönmek yeni kontrolleri devre dışı bırakır. Sadece geçici test DB'sinde rollback ve yeniden migrate doğrulandı.

## Kabul kanıtı

33 yeni senaryo; nihai kod üzerinde:

| Kontrol | Sonuç |
|---|---|
| Tam SQLite | 689 geçti, MariaDB'ye özel 18 atlama; 2.775 assertion |
| Tam MariaDB 11.8 | 707 geçti; 2.966 assertion |
| Pint | 568 PHP dosyası geçti |
| Vite üretim derlemesi | Geçti |
| `git diff --check` | Geçti |
| İzole DB migration rollback / yeniden migrate | Geçti |
| Masaüstü/mobil gerçek tarayıcı | Kategori kaydet/aç, ters aralık, çoğaltma, sıfırlama; public/üye özetleri, gerçek upload reddi, TR/EN geçti; JavaScript hatası yok |

`CompetitionPhotoTechnicalRulesTest` 17, `CompetitionPhotoRulesIntegrationTest` 3, `CategoryPhotoRulesStepTest` 13 senaryo içerir. Sınır eşitliği ve ihlali, iki eksende alt/üst DPI, EXIF/JFIF önceliği, PNG birimleri, eksik birim, gerçek portföy ölçüleri, geri çekilmiş dosya yeniden kontrolü ve kurum yetkisi doğrulanır. Önceki anonimlik, kota/başvuru yarışları, jüri/sonuç ve hesap güvenliği testleri de tam regresyon koşusundadır.

[Ham test kanıtı](2026-09-05-category-photo-rules-evidence.txt) · [Kaynak SHA-256 kaydı](2026-09-05-category-photo-rules-source-sha256.json) · [Tarayıcı QA raporu](/Users/mcsaygili/Documents/ChatGPT/TFSF/reports/2026-09-05-fotograf-teknik-kosullari-qa.md)

## Kalan işler

PK-06.02'nin ek belge türü/boyutu/adedi, PK-06.03/04'ün fotoğraf beyanları, kategori hikâyesi, sıralı seri ve ek dosyaları açık. Etkin yarışmada kuralların değişmez sürümü ve mevcut eserlerin yeniden değerlendirilmesi ortak revizyon protokolüne dahil edilmeli. Geçici/parçalı yükleme, dosya iş günlüğü, kesinti onarımı ve işleme/kilit süresi hacim testleri de tamamlanmadı. Legacy alanlarını dolduracak importer, gerçek dosyaların koşullarla mutabakatı, staging ve kullanıcı pilotu yapılmadı.
