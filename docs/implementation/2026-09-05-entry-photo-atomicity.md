# Başvuru kotası ve fotoğraf işlemlerinin atomikliği

5 Eylül 2026 — PK-06.05 ve PK-06.06 alt kapsamı. Yerel uygulama, gerçek ortam ve pilot kabul açık. Dal: `codex/tfsf-security-foundation`.

## Kaynak davranış ve bulunan sorunlar

Eski `ThirdParty/Frontend/Controllers/Yarisma/YarismaKayit.php` kategori ve fotoğraf verilerini topluyor; `ThirdParty/Frontend/Models/Yarisma/YarismaKayitModel.php::add()` başvuru, fotoğraf ve ek dosya satırlarını ekleyip geçici dosyaları kalıcı alana taşıyor. İncelenen modelde bu adımlar ortak transaction içinde değildi; `@rename` başarısı denetlenmeden başarı yanıtı üretiliyordu. Fotoğraf adı/tarihi/yeri/sırası/hikâyesi, kategori hikâyesi, ek dosya ve grup yapısı da bu kaynaklarda mevcut; bu paket onların tamamının yeni sistemde karşılandığı anlamına gelmez.

Yeni sistemde fotoğraf kotası önce okunuyor, dosyalar ve kayıt daha sonra oluşturuluyordu. İki farklı yükleme aynı son kontenjanı görebiliyordu. Aynı hash için benzersizlik olsa da kullanıcıya doğrulama yanıtı yerine veritabanı hatası çıkabiliyordu. Başvuru gönderimi entry satırını kilitlerken fotoğraf silme/ekleme ve kategori ekleme aynı kilide katılmıyordu. Taslak silme dosyayı veritabanı silmesinden önce kaldırıyordu. Revizyon kaydı hatası da yazılmış dosyaları açıkta bırakabiliyordu.

## Uygulanan davranış

- Fotoğraf ekleme, geri çekme/silme ve kategori ekleme, `CompetitionEntryService::submit()` ile aynı başvuru satırını ilk kilit olarak kullanır. Aynı başvurudaki yazmalar sıraya girer; farklı başvurular için tek bir küresel kilit yoktur.
- Bekleyen istek kilidi aldıktan sonra başvuru ve alt başvuruyu yeniden okur. Önceden yüklenmiş taslak durumu veya kategori kotası kullanılarak güncel sınır aşılamaz.
- Aktif fotoğraf sayımı ve aynı hash kontrolü kilitli okumadır. İki yüklemenin son kontenjanı paylaşması engellenir; yinelenen fotoğraf normal doğrulama hatası alır.
- Başvuru gönderimindeki zorunlu fotoğraf ve kota kontrolü aktif fotoğrafları sayar; geri çekilmiş bir eser teslim için yeterli sayılmaz.
- Son fotoğrafı silme ile gönderme yarışında yalnız bir işlem başarılı olabilir. Kategori ekleme ile gönderme yarışında boş kategori içeren onaylı başvuru oluşmaz.
- Fotoğraf kaydı, değerlendirmeyi yeniden açma işlemleri ve revizyon olayı aynı transaction içindedir. Revizyon olayı başarısızsa yeni fotoğraf satırı geri alınır.
- Yeni orijinal ve anonim jüri kopyasının yolları rollback temizliğine kaydedilir. İç veya üst transaction geri alınırsa bu yeni dosyalar temizlenir.
- Taslak fotoğrafın fiziksel dosyaları ancak veritabanı silmesi kesinleştikten sonra kaldırılır. Üst transaction geri alınırsa hem satır hem dosyalar korunur.
- Değerlendirme dönemindeki geri çekme fiziksel dosyaları korur. Aynı isteğin tekrarı ikinci bir revizyon olayı veya ikinci bir yeniden açma bildirimi üretmez.
- Jüriye yeniden açma bildirimi transaction kesinleştikten sonra kuyruğa verilir; geri alınmış revizyon için bildirim gönderilmez.

`CompetitionSubmissionPhotoService`, `CompetitionEntryService` ve süreç testlerinin izole storage desteği güncellendi. Şema migration'ı eklenmedi. Mevcut anonim JPEG üretimi ve kaynak portfolyodan ayrı yarışma kopyası davranışı korunur.

## Doğrulama

10 yeni senaryo eklendi. Son kod üzerinde:

| Kontrol | Sonuç |
|---|---|
| Tam SQLite | 656 geçti; MariaDB'ye özel 18 test atlandı; 2.708 assertion |
| Tam MariaDB 11.8 | 674 geçti; 2.899 assertion |
| Son dört süreç testi | 4 geçti; 46 assertion |
| Pint | 559 PHP dosyası geçti |
| `git diff --check` | Geçti |

PHP 8.5, SQLite bellek veritabanı ve ayrı `tfsf_testing` MariaDB veritabanı kullanıldı. Geçici test veritabanı konteyneri koşulardan sonra kapatıldı. Gerçek veride migration, aktarım veya dağıtım yapılmadı. Kategori uygunluğu ve kullanıcı profilinin geçerliliği yarışlardan önce ayrıca doğrulanıyor; zaten geçersiz bir başvurunun reddi başarı kanıtı sayılmıyor.

Altı davranış/hata senaryosu `CompetitionEntryAtomicityTest` içinde: üst transaction rollback dosya temizliği, silme rollback'i, eski taslak durumu, eski kota, revizyon olayı hata enjeksiyonu ve yinelenen geri çekme. Dört MariaDB süreç senaryosu `CompetitionEntryConcurrencyTest` içinde: son kontenjan, aynı fotoğraf, gönder/sil ve gönder/kategori ekle yarışları.

Süreç testleri gerçek HTTP kernel, kullanıcı oturumu, controller doğrulaması, servis, MariaDB ve dosya sistemi üzerinden çalışır. İki süreç aynı başlangıç bariyerinden çıkar. Her senaryonun ayrı geçici local/public storage kökü vardır; gerçek portfolyo veya yarışma dosyaları kullanılmaz. Başarı/hata yanıtlarıyla birlikte son DB durumu ve dosyalar denetlenir.

Önceki fotoğraf anonimliği, portfolyo revizyonu, değerlendirme/sonuç ve ağırlıklı puan testleri de regresyona dahildir. [Ham test kanıtı](2026-09-05-entry-photo-atomicity-evidence.txt).

## Açık kalan kapsam

1. Jüri puan kaydı/finalize, kurum onayı, final tur açma ve sonuç yayımlama henüz bu başvuru kilidinin tamamına katılmıyor. Üye revizyonuyla aynı anda bu işlemlerin yapılması için ortak kilit/sürüm protokolü ve testler PK-06/07/08/09 içinde tamamlanmalı. Bu paket, değerlendirmeye alınan sürümün tüm aktörler karşısında değişmezliği kabulünü vermez.
2. Süreç aniden öldürülürse rollback/afterCommit callback'i çalışamayabilir. Kalıcı dosya iş günlüğü, kuyrukta yeniden deneme ve yetim dosya uzlaştırması PK-06.06 kapsamında açık. Storage silme hatası veya işletim sistemi kesintisine karşı kalıcı onarım henüz tamamlanmadı. Bildirimde commit sonrası teslim de kalıcı outbox yerine geçmez; PK-13.02 devam ediyor.
3. Fotoğraf tipi/byte/piksel/DPI kuralları, beyan alanları, sıra/hikâye, kategori hikâyesi, seri ve ek dosyalar PK-06.01–04 kapsamında devam ediyor. Geçici/parçalı yükleme kimliği ve uçtan uca tekrar deneme anahtarı bu pakette eklenmedi.
4. Mevcut geri çekme gerekçesinin kullanıcıdan alınması ve tarih/yetki politikasının bütün aktörlerde ortak uygulanması açık.
5. Dosya yazımı ve anonim kopya üretimi başvuru transaction'ı içinde; doğruluk sağlandı ancak büyük dosya/çoklu istek hacminde kilit bekleme süreleri ölçülmeli. Geçici hazırlama ve kısa kalıcılaştırma adımı sonraki yükleme çalışmasına dahil edilmeli.
6. Gerçek veri aktarımı, staging/pilot, kesim ve geri dönüş provası yapılmadı. Tüm yeniden geliştirme planı veya PK-06 bütünü kabul edilmedi.
