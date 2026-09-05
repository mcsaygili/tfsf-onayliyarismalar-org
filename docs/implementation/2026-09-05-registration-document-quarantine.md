# Ön kayıt belgelerinde karantina ve tarama

On altıncı yerel uygulama paketi, PK-05.02'nin PDF güvenliği alt kapsamını uygular. Ön kayıt belgesinin yüklenmesi artık indirme veya inceleme için yeterli değildir: dosya özel depoda karantinaya alınır; geçerli tarama kanıtı oluşana kadar indirme, incelemeye gönderme, onay ve bu onaya dayalı fotoğraf katılımı kapalı kalır.

## Dosya ve tarama politikası

`PdfDocumentScanner`, qpdf ile yapıyı kontrol eder ve JSON 2 çıktısındaki çözülmüş PDF nesnelerini inceler. Bozuk, şifreli, sayfasız, JavaScript/otomatik eylem, harici eylem/bağlantı, ek dosya, XFA ve çoklu ortam içeriği olan belgeler reddedilir. Sıradan, etkin içeriksiz AcroForm alanları kabul edilir. Dosya değiştirilmez veya “temiz PDF” olarak yeniden üretilmez. qpdf'nin yapısal kontrolü bütün PDF sorunlarını tespit etme garantisi vermez; nesne incelemesi, uygulamanın izin verilen içerik politikasını ayrıca uygular. [qpdf CLI](https://qpdf.readthedocs.io/en/latest/cli.html), [qpdf JSON](https://qpdf.readthedocs.io/en/stable/json.html).

Yapı kontrolünü geçen dosya `clamdscan --stream` ile ClamAV daemon'una gönderilir. Başarılı tarama sonucu belge SHA-256'sı, `qpdf-clamav-pdf-v1` politika sürümü, tarama zamanı ve ClamAV sürüm çıktısıyla kaydedilir. Erişim için bu kanıtın kayıtlı dosya hash'i ve güncel politikayla eşleşmesi gerekir. İndirme sırasında gerçek dosyanın hash kontrolü de korunur. Komutlar shell birleştirmesi olmadan argüman dizisiyle başlatılır. Daemon TCP arayüzü kimlik doğrulama sağlamaz; üretimde özel Unix socket/kapalı ağ kullanılmalıdır. [ClamAV tarama](https://docs.clamav.net/manual/Usage/Scanning.html), [daemon protokolü](https://docs.clamav.net/manual/Usage/ClamdProtocol.html).

Her süreç için 30 saniye, toplam araç çıktısı için 8 MB sınırı vardır. Taranacak dosya en fazla 10 MB olmalı ve kayıtlı byte sayısıyla eşleşmelidir. Tarama rastgele işlem kimliği altında özel bir kopyada yapılır; kopya öncesi kayıtlı hash ile ve araç çalıştıktan sonra yeniden karşılaştırılır. Normal başarı/hata yollarında geçici kopya silinir. Ham PDF, araç çıktısı, dosya yolu veya kişisel veri hata mesajlarına eklenmez. Antivirüs sonucu bütün olası zararlıları tespit etme garantisi değildir.

## Kuyruk ve eşzamanlılık

Yükleme transaction'ı commit olduktan sonra `ScanRegistrationDocument` işi, ayrı `document_scans` veritabanı bağlantısının `document-scans` kuyruğuna gönderilir. Kuyruğa yazma başarısızsa belge `pending` kalır; kurtarma komutu bulabilir. Yükleme transaction'ı geri alınırsa tarama işi yayımlanmaz.

Tarama sahiplenme ve sonuç kaydı kısa transaction'larda yarışma kilidini kullanır; qpdf/ClamAV bu kilit açıkken çalışmaz. Sahiplenme süresi 300 saniye ve işlem tokenı UUID'dir. Aynı belgeyi alan iki işçi tek tarama üretir. Eski işçi süresi dolduktan sonra yeni sahiplenmenin sonucunu ezemez. MariaDB süreç testinde ortaya çıkan eski okuma görüntüsü hatası, yarışma kimliğinin transaction öncesinde alınması ve transaction içindeki ilk okumanın yarışma kilidi olmasıyla giderildi.

İş zaman aşımı 150 saniye; kuyruk `retry_after` 240 saniye; yeniden deneme aralıkları 60/300 saniye, toplam deneme sayısı 3. Tarayıcı yoksa, daemon erişilemiyorsa veya işlem başarısızsa durum `error` kalır. Geçerli kanıt oluşmadan erişim açılmaz. Tarama sonucu ve gerekçesi sistem aktörlü denetim olayına yazılır; olay kaydı başarısızsa güvenilir sonuç commit edilmez. Tarama, belgenin byte'ını veya ön kayıt form sürümünü değiştirmez; gönderim/karar geçerli kanıtı yeniden denetler.

Kurtarma komutu:

```sh
php artisan tfsf:scan-registration-documents --limit=20
php artisan tfsf:scan-registration-documents --document=DOCUMENT_UUID
```

Normal tarama; bekleyenleri, süresi dolmuş/hatalı işleri, eksik sahiplenme zamanı olanları ve eski/eksik temiz kanıtları kapsar. Varsayılan limit 20, üst sınır 100. Reddedilen dosyalar normal komutla otomatik yeniden taranmaz; belirli belge seçilirse tekrar değerlendirilebilir. Güncel geçerli temiz kanıt atlanır. Bulunamayan açık belge kimliği başarısız çıkış kodu verir. Zamanlayıcı/servis kurulumu bu pakette gerçek ortama uygulanmadı.

## Ekran davranışı

Üye ve kurum/temsilci inceleyicisi, güncel ve geçmiş belge sürümlerinde bekliyor/sürüyor/tamamlandı/reddedildi/hata durumunu görür. Yalnız güvenilir belge bağlantı olur; eski politika kanıtı “bekliyor” görünür. Durum sayfa yenilendiğinde güncellenir. Asgari sayıda temiz belge olsa bile başka bir güncel belge karantinadaysa gönderim engellenir. Üye taslak veya düzeltme aşamasında reddedilen belgeyi kaldırabilir/değiştirebilir. Önceki sahiplik, görev kapsamı ve tarih kontrolleri korunur.

Mobil inceleyici formunda kutu boyutlandırması, kart araları, belge bağlantısının kontrastı ve durum metninin ayrı satırda gösterimi düzeltildi. Değişiklikler ön kayıt ekranıyla sınırlıdır.

## Şema geçişi ve geri dönüş

`2026_09_05_250000_quarantine_registration_documents.php` tarama durumunu, sahiplenme tokenı/zamanını, deneme sayısını ve kanıt alanlarını ekler. **Mevcut belgeler de varsayılan `pending` olur.** Önceden onaylanmış bir kayıt, gerekli güncel belgeleri yeniden taranana kadar fotoğraf katılımı için yeterli sayılmaz. Ön kayıt zorunluluğu olmayan yarışmalar bu kapıya tabi değildir.

**Rollback kayıpsız değildir:** eski kod bütün eşlenmiş dosyaları indirmeye açacağı için `down()`, geçerli temiz kanıtı olmayan belge eşlemelerini siler; sonra tarama alanlarını kaldırır. Özel dosyalar ve ön kayıt/karar satırları korunur. Tekrar migration, kalan belgeleri yeniden karantinaya alır. Silinen eşlemelerin geri getirilmesi DB yedeği gerektirir. Üretimde bakım penceresi, kod/şema sürüm uyumu ve DB+özel dosya yedeği birlikte ele alınmalıdır; yalnız kod geri dönüşü güvenlik açısından yeterli değildir.

İzole `tfsf_results_ui` veritabanındaki prova: 1 temiz eşleme korundu, 1 reddedilmiş eşleme kaldırıldı, 2 özel dosya ve kayıt/karar satırları değişmedi; tekrar migrate ile kalan belge karantinaya döndü ve eski onay yeniden tarama gerektirdi. Gerçek uygulama veritabanında migration, aktarım veya dağıtım yapılmadı.

## Çalıştırma gereksinimleri ve açık operasyon işleri

qpdf, clamdscan ve ClamAV daemon'u ayrı servis kullanıcısıyla kurulmalı. `.env.example` içindeki `DOCUMENT_QPDF_BINARY`, `DOCUMENT_CLAMDSCAN_BINARY`, `DOCUMENT_CLAMD_CONFIG` yolları çalışma ortamına uyarlanmalı. Web ve işçinin aynı özel storage ve veritabanına erişmesi gerekir. İşçi komutu:

```sh
php artisan queue:work document_scans --queue=document-scans --tries=3 --timeout=150
```

Güncel üretim virüs imzalarının kurulması/yenilenmesi, imza yaşı ve daemon sağlığı izleme, kuyruk gecikmesi/başarısız iş uyarıları, kurtarma komutunun periyodik çalışması, worker kaynak sınırları ve ayrıcalıksız kullanıcı kurulumu açık operasyon işleridir. İmza güncellenmesi mevcut temiz kayıtları kendiliğinden geçersiz kılmaz; yeniden tarama politikası için politika sürümü değişimi veya kontrollü kanıt geçersizleştirme gerekir. Bu sürümde imza tazeliği uygulama tarafından zorlanmaz.

SIGKILL/host kesintisi sonrası geçici dosya artıkları, belge saklama süresi, disk kotası, orphan eşleme uzlaştırması, bütün eski belge aktarımı ve gerçek kullanıcı PDF çeşitliliğiyle pilot kabul de açık kalır.

## Doğrulama

10 karantina testi, 9 gerçek araç entegrasyon testi ve 1 MariaDB süreç testi eklendi. Mevcut 16 ön kayıt akış testi, yalnız test sınıfında kullanılan açık sentetik tarayıcıyla işlevleri doğrular; üretim kodunda test ortamına bağlı tarama atlatması yoktur. Entegrasyon testleri gerçekten qpdf ve ClamAV çalıştırır: olağan PDF, sıkıştırılmış nesne ve kaçışlı JavaScript adı, ek dosya/XFA, normal form, bozuk/şifreli PDF, zararsız tespit işareti, erişilemeyen daemon, çıktı sınırı ve zaman aşımı.

Yerel gerçek araçlar: PHP 8.5.10, qpdf 12.2.0, ClamAV 1.4.3. Geçici test daemon'u yalnız sentetik `TFSF.Test.Document` imzasıyla çalıştırıldı; **bu imza seti üretim antivirüs veritabanı değildir ve gerçek tehdit kapsamı ölçülmedi.** CI, aynı zararsız imzayı ayrı daemon'da kurup entegrasyon testlerini etkinleştirir. CI başlatma betiği yerelde çalıştırıldı; uzak CI koşusu yapılmadı.

**Nihai SQLite: 811 geçti / 3.680 assertion, MariaDB'ye özel 35 atlama. Nihai MariaDB: 846 geçti / 4.075 assertion.** Pint 634 PHP dosyasında, Vite derlemesi ve `git diff --check` başarılı. Her iki tam koşuda da gerçek araç entegrasyon testleri etkinleştirildi.

Tam sonuçlar ve komutlar [doğrulama çıktılarında](2026-09-05-registration-document-quarantine-evidence.txt), kaynak dosyalar [SHA-256 manifestinde](2026-09-05-registration-document-quarantine-source-sha256.json) kayıtlıdır. Chrome'da gerçek kuyruk işiyle tarayıcı hatası, kurtarma, ret, kaldırma, gönderim ve kurum onayı; TR/EN, masaüstü/mobil ve 6 ekran görüntüsü doğrulandı. İki toplu görsel inceleme yapıldı; son turda konsol/sayfa hatası yok. Yalnız sentetik veri ve `array` posta sürücüsü kullanıldı.

Bu paket PK-05.02'nin üretim kabulünü veya 84 işin tamamını kapatmaz. Genel hedef aktiftir.
