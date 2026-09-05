# Yayın sürümüne bağlı sonuç arşivi

On üçüncü yerel uygulama paketi. PK-09.05, PK-10.01/10.02/10.04/10.05 içindeki kamu sonuç görünümü, EYS tarihsel inceleme/CSV ve arşiv görsel bütünlüğünü geliştirir. Üye puan kartı, bütün raporlar, eski veri aktarımı ve kişisel veri görünürlük politikasının bütünü henüz tamamlanmadı.

## Sorun ve yeni davranış

`ResultPublicationService` zaten yayın anı isimlerini kaydediyordu; kamu liste/detay ekranı, görsel erişimi ve EYS sonuç CSV'si bu kaydı kullanmıyordu. Profil, kategori/ödül referansı veya yarışma adı sonradan değiştiğinde geçmiş ilan da değişebiliyordu. Eski görsel adresi güncel yarışma kopyasına bağlıydı.

Kamu sonuç listesi, araması ve detay sayfası artık yarışmanın işaret ettiği güncel yayın kaydını kullanır. Yarışma adı/konusu/türü, kurum ve kategori adları, katılımcı adı, sıra/ortalama puan, ödül adı ve sayılar yayın anından okunur. Arama metni de yayın sırasında saklanır. Yeni kayıt şeması sürüm 2'dir. Eski sürüm 1 kayıtlarında yalnız gerçekten kaydedilmiş alanlar gösterilir; eksik konu/tür/sayı/görsel güncel tablolardan uydurulmaz.

Her yeni yayında ödüllü fotoğrafların temizlenmiş jüri kopyaları ayrı özel disk dosyalarına kopyalanır. Dosya kaydı yayın kimliği, kaynak fotoğraf kimliği, SHA-256, MIME ve boyut taşır; kaynak fotoğrafa foreign key ile bağlı değildir. Kaynak fotoğraf silinmesi veya canlı dosyanın değişmesi arşiv kopyasını değiştirmez. Her erişimde aynı açık dosya üzerinden checksum doğrulanır ve dosya başına dönülerek aynı içerik sunulur. Eksik/bozuk arşiv dosyasında orijinale veya canlı kopyaya dönüş yoktur.

Yeni sürüme ait kamu görsel adresi yayın kimliğini içerir. Eski `result.photos.show` adresi de yalnız güncel, erişilebilir yayındaki arşiv kaydını çözer. Zamanı gelmemiş, geri çekilmiş, eski sürümde kalmış ya da yarışması askıya alınmış/iptal edilmiş/yayında olmayan bir kayıt kamuya açılmaz. Yanıtlar `private, no-store` kullanır. Önceden indirilmiş içerik geri çağrılamaz; dağıtım sırasında önceki uzun süreli CDN/tarayıcı cache politikası ayrıca ele alınmalıdır.

## Düzeltme ve yetkili görünüm

Geri alma yalnız `results_publication_version` ile işaret edilen sürümü geri çeker; ilişki listesindeki en büyük veya ileri tarihli başka sürümü hedeflemez. Yeniden yayımlama yeni sürüm oluşturur. Eski snapshot/model içeriği yerinde güncellenemez; eski kayıt model üzerinden silinemez. Bildirim zamanı ve geri alma/düzeltme bilgisi güncellenebilir.

EYS yayın geçmişindeki sürüm bağlantısı, `version` parametresiyle o sürümün metnini ve özel arşiv görselini açar. Geçmiş veya planlı yayınları incelemek mevcut EYS yarışma yönetimi yetkisini gerektirir. Görsel ayrıca yarışma/yayın kapsamıyla eşleştirilir. Kamu geçmiş tablosu ileri tarihli yayın notlarını göstermez. Yeni alan ve mesajlar TR/EN tanımlıdır; saklanmış tur adı mevcut kayıt dilinde korunur.

Sonuç CSV'si yayındaki güncel sürümü veya yetkilinin açıkça istediği tarihsel `version` değerini kullanır. Taslak sonuç CSV'si henüz yayımlanmamış güncel veriden üretilir. CSV'ye yayın sürümü sütunu ve dosya adına sürüm eklendi. Sonuç ve katılım CSV metinlerinde formül başlatan karakterler için apostrof koruması uygulanır. Kamu galerisi yalnız ödüllü eserleri gösterir; yetkili sonuç CSV'si kaydedilmiş bütün sonuç satırlarını içerebilir.

## İşlem ve dosya bütünlüğü

Snapshot ve dosya kayıtları yarışma kilidi ve yayın transaction'ı içinde oluşturulur. Eksik veya JPEG/PNG/WebP olmayan güvenli görsel yayını durdurur. Normal exception veya dış transaction rollback'inde yayına ait dosya dizini de silinir; denetim kaydı hatası test edildi. Bildirim commit sonrasındadır. Eşzamanlı yayın/ödül ve yayın/üye revizyon testleri artık ayrı geçici disk kökünde gerçek temizlenmiş görsellerle çalışır.

Snapshot alanları için model güncelleme/silme koruması, arşiv dosya kayıtları için model güncelleme koruması vardır. Yayının yarışma foreign key'i `RESTRICT` olduğundan yarışmanın fiziksel silinmesi yayın geçmişini cascade ile yok edemez. Bunlar kötü niyetli DB yöneticisine karşı WORM veya imzalı kayıt garantisi değildir. Kontrollü kişisel veri maskeleme/saklama akışı ayrıca tasarlanmalıdır.

## Geçiş ve denetim

`2026_09_05_220000_freeze_result_publication_assets.php` yayınlara `snapshot_version` ve `search_text`, ayrıca `competition_result_assets` tablosunu ekler. Eski yayınların snapshot JSON'u değiştirilmez; arama alanı yalnız kaydedilmiş isimlerden 500 kayıtlık parçalarla doldurulur. Eski görsellerin yayın anındaki doğru byte'ları bilinmeden güncel dosyalar tarihsel arşivmiş gibi atanmaz.

Eksikleri salt okunur raporlamak için:

```sh
php artisan tfsf:audit-result-archives --verify-files
php artisan tfsf:audit-result-archives --competition=YARISMA_UUID --verify-files
```

Komut eksik yayın kaydı, eski kısmi snapshot, tutarsız geri çekilmiş güncel yayın, eksik arşiv görseli ve istenirse checksum uyuşmazlığını bildirir. Sorun varsa exit 1; sorun yoksa exit 0. Veri veya dosya onarmaz. Kapsamı sonuç yayın zamanı atanmış yarışmaların işaret edilen sürümüdür; tüm eski sürüm/orphan dosya envanteri değildir.

Canlı geçişten önce rapordaki kayıtlar tarihsel kaynakla doğrulanmalı, eksikler kontrollü aktarım/yeniden yayınla giderilmeli ve cache geçişi planlanmalıdır. Snapshot kaydı hiç olmayan bir sonuç kamuya güncel tablolardan sunulmaz. Kısmi eski snapshot mevcut kayıtlı metinle ve eksik arşiv açıklamasıyla gösterilir. Bu, veri aktarımı tamamlandı iddiası değildir.

DB ve `result-publications/` özel dosyaları birlikte yedeklenmelidir. Rollback arşiv tablosunu ve sürüm/arama sütunlarını kaldırır; dosyaları otomatik silmez. Rollback ardından tekrar migrate, mevcut JSON'u korur fakat eski kayıt olarak işaretler; aynı tarihsel görsel eşlemesini geri almak için DB/dosya yedeği gerekir. Test yalnız izole `tfsf_results_ui` şemasında yapıldı. Gerçek DB migration, aktarım ve dağıtım yapılmadı.

## Doğrulama

12 yeni özellik testi; profil/referans değişimi, arama ve CSV, dosya/kimlik kopması, geri alma/yeniden yayın, planlı yayın ve gelecekteki notlar, eksik/bozuk dosya, rollback temizliği, yetki/kapsam, kısmi eski kayıtlar, yerinde değişiklik ve yarışma silme kısıtı. Güncellenen üç MariaDB süreç testiyle odaklı grup 15 test / 190 assertion geçti.

Nihai tam SQLite: **766 geçti / 3.352 assertion**, MariaDB süreç/migration testleri için **30 atlama**. Nihai tam MariaDB: **796 geçti / 3.692 assertion**. Pint **605 PHP dosyasında**, Vite derlemesi ve `git diff --check` başarılı. İzole UI şemasında son migration rollback/yeniden migrate sonrası eski snapshot JSON'unun özeti değişmedi ve arama alanının tarihsel adlardan yeniden doldurulduğu doğrulandı.

[Doğrulama çıktıları](2026-09-05-frozen-result-publications-evidence.txt) · [23 kaynak dosyasının SHA-256 manifesti](2026-09-05-frozen-result-publications-source-sha256.json).

Chrome'da gerçek ödül/yayın formu kullanıldı; canlı isimler değiştirilip kaynak görseller silindikten sonra ilan metni ve arşiv görselinin SHA-256 özeti aynı kaldı. Tarihsel EYS görünümü, geri almadan sonra kamu erişiminin kapanması, masaüstü/mobil taşma ve İngilizce yayın geçmişi doğrulandı. Sadece sentetik hesap/veri, ayrı storage ve `array` posta sürücüsü kullanıldı. Browser plugin olmadığı için Playwright kullanıldı.

## Açık kapsam

Üye yarışma sayfasındaki sonuç bölümü ve puan kartı hâlâ canlı değerlendirme ilişkilerini kullanıyor; bunlar da yayın sürümüne bağlanmalı. Bütün kurum/temsilci raporları, sonuç bildirgesi, Excel ve ZIP çıktıları, tarihsel yayıncı/kimlik politikası, ödülsüz eserlerin tarihsel görselleri, ayrıntılı puan arşivi ve kişisel veri maskeleme/saklama çalışmaları açık.

Kamu listelemesi sayfalıdır; tek bir yarışmanın snapshot'ı ve CSV sonuçları belleğe alınır. Büyük arşivlerde sorgu/JSON boyutu, dosya checksum maliyeti ve yayın sırasında kilit beklemesi ölçülmelidir. Süreç aniden öldürülürse özel dizinde referanssız dosya kalabilir; kalıcı dosya günlüğü/outbox ve orphan uzlaştırması açık. Eski veri geri kazanımı, pilot kabul ve geniş erişilebilirlik denetimi tamamlanmadı.
