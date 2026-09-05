# Yetkili doğrudan ön kayıt ve belge sayısı istisnası

On sekizinci yerel uygulama paketi, PK-05.03'ün doğrudan kayıt/özel yetki alt kapsamını uygular. Yarışmanın atanmış inceleyicisi, ayrı bir yetki verilmişse üyeyi doğrudan onaylayabilir. Yetki verme ve katılımcı onayı ayrı işlemler ve ayrı denetim kayıtlarıdır.

## Eski davranış ve kapsam kararı

Eski `ThirdParty/Temsilci/Controllers/AktifYarismaBilgileri.php:183` onay işlemini başlatır. `TemsilciAktifYarismaModel.php:37` mevcut yarışma/üye kaydının `onay_tarihi` alanını günceller; bu metodun adı “ekle” olsa da yaptığı işlem yeni satır oluşturmak değildir. `TemsilciArsivYarismaIslemModel.php:188` ise sıra numarasıyla yeni katılımcı satırı oluşturur; takip eden yardımcı `MAX(sira_numarasi)` değerini okur. Bunlar aynı yöntem veya aynı tarih kuralı gibi değerlendirilmedi.

Planın doğrudan kayıt/istisna gereksinimi yeni sistemde kontrollü bir işlemle karşılandı: mevcut taslak/bekleyen/düzeltme istenen/reddedilen ön kayıt gerekçeli onaylanabilir; hiç kayıt yoksa önce sıradaki kayıt oluşturulur. Mevcut onay tekrar yazılamaz. Arşiv/final sonrası geçmiş kaydı değiştirme bu pakette açılmadı. Ayrı ön kayıt takviminin eski kaynakta açık bir karşılığı bu incelemede doğrulanmadı; mevcut fotoğraf başvuru penceresi kullanılıyor.

## İki katmanlı yetkilendirme

EYS kullanıcısında Institution modülünde hem `institution.competitions.manage` hem **`institution.registration_exceptions.manage`** gerekir. Genel yarışma yönetim izni tek başına yetmez. Eksik özel izin kaydı hata üretmeden erişimi reddeder. Yetki kararı sırasında EYS hesabı ve izinler yeniden okunur, önceki modül bağlamı geri yüklenir.

Yeni `registration_exception_grants` tablosu yarışma + aktör türü + aktör kimliği için tek kayıt tutar. Aktör yalnız aktif kurum görevlisi veya temsilci olabilir. Ön kayıt inceleyicisi kurumsa aynı kurumun görevlisi; temsilciyse yarışmaya atanmış temsilci yetkilendirilebilir. Üye, jüri veya başka kurum adına izin üretilemez. Hiçbir kurum/temsilci hesabı otomatik istisna hakkı kazanmaz.

İzin kayıtları silinmez: aktiflik, sürüm, 10–2000 karakter gerekçe, son EYS aktörü ve zaman korunur. Her verme/kaldırma `CompetitionStatusLog` içinde ayrıca kayıtlıdır. Kapsam dışına çıkmış veya pasifleşmiş mevcut alıcının yetkisi EYS ekranından kaldırılabilir; böyle bir alıcıya yeni aktif yetki verilemez. Kullanımda hem kayıt hem güncel hesap/görev kapsamı yeniden denetlenir. Görev tekrar aynı kapsamda geçerli hale gelirse kaldırılmamış izin kullanılabilir; atama değişikliği izni otomatik silmez. Süreli yetki/otomatik sonlandırma ayrıca yapılmış sayılmaz.

Config kayıt defterine özel izin eklendi. **Mevcut `ModuleRoleSeeder` çalıştırılırsa Institution `admin` rolüne modülün diğer izinleri gibi bu izin de atanır.** Özel bir atama gerektiren operasyon politikasında bu rol kapsamı geçiş öncesinde incelenmelidir. Gerçek ortamda seeder, izin ataması veya migration çalıştırılmadı.

## Doğrudan işlem

Yetkili görevli ön kayıt listesinden yarışmayı açar, tam e-posta adresiyle üyeyi bulur, mevcut kayıt numarası/durumu ve güncel belge sayısı/tarama durumunu görür. Arama POST ve dakikada 10 istek sınırıyla çalışır; yetki denetimi üye sorgusundan önce yapılır. Genel üye kataloğu veya T.C. kimlik numarası listesi sunulmaz. İzin ve doğrudan işlem ekranlarının yanıtları `private, no-store` kullanır.

Gönderim hem ön kayıt sürümünü (yeni kayıt için 0) hem izin sürümünü taşır. Yarışma mutex'i transaction içindeki ilk kilittir. İzin kaldırma ve doğrudan onay aynı kilidi alır. İki onay isteğinden biri kaydı/numarayı oluşturur; diğeri eski sürüm nedeniyle reddedilir. Kaldırma önce gerçekleşirse onay reddedilir; onay önce gerçekleşirse geçerli onay korunur ve sonraki işlemler kapatılır.

Kayıt yoksa yarışma sayacı kilit altında artırılır; `direct_registered` olayı görevli adına kaydedilir. Onay `approval_source=direct`, güncel aktör/zaman/gerekçe, `exception_grant_id`, belge istisnası ve `exception_approved` olayıyla yazılır. Olay; önceki durum, izin kimliği/sürümü ve güncel belge kimliklerini içerir. Üyenin fotoğraf başvurusu, katılım beyanı veya rızası görevli adına üretilmez. Yeni doğrudan kayıtta `submitted_at` idari inceleme zamanıdır; `approval_source` ve olay türü bunun üyenin gönderimi olmadığını ayırır.

İstek doğrulama hatası GET formuna yönlenir; seçilmiş üye ve yazılan gerekçe korunur. POST arama adresine GET dönerek 405 üretmez. Eski kayıt/izin sürümünde güncel durum tekrar gösterilir. Form yeni durum üzerinde kullanıcı tarafından tekrar değerlendirilmelidir.

## İstisnanın kesin sınırı

`documents_waived` yalnız gerekli asgari belge adedini kaldırır; başlangıçtaki `document_min` değiştirilmez. Checkbox varsayılan kapalıdır ve istek açık boolean içermelidir. Mevcut her belge hâlâ güvenilir tarama kanıtı, dosya varlığı ve SHA-256 eşleşmesi gerektirir. Bekleyen, reddedilmiş, hatalı, kayıp veya değiştirilmiş güncel belge istisnayla onaylanamaz. Üç belge üst sınırı sürer. Belgeyi taranmış gibi işaretleyen uygulama yolu eklenmedi.

Hesap etkinliği, e-posta doğrulaması, üye uygunluğu/kısıtları, TFSF altyapısı, yarışmanın kamuya açık olması, başvuru tarihleri ve final/yayın sınırı korunur. Belge istisnası bu koşulları değiştirmez. Üye ekranı doğrudan onayı ve gerekçesini gösterir; belge istisnası varsa yeniden belge yüklemesini isteyen genel metin yerine geçerli durumu açıklar.

Normal düzeltme kararı ve yeniden gönderim istisna alanlarını temizler; üye normal belge koşullarını tekrar tamamlar. Kullanılarak fotoğraf başvurusu gönderilmiş onayın geri alınmasını engelleyen mevcut kural korunur. Yetkinin kaldırılması geçmişte yetkili verilen onayları topluca geri almaz; katılım iptali ayrı süreçtir.

## Geçiş ve geri dönüş

Migration `2026_09_05_270000_authorize_registration_exceptions.php` yeni izin tablosunu ve üç ön kayıt alanını ekler. Mevcut kayıtlarda varsayılan `documents_waived=false`, `approval_source=normal`, izin bağlantısı null; mevcut kullanıcılara yarışma izni oluşturulmaz.

Rollback izin tablosunu ve üç alanı kaldırır. Ön kayıt, özgün belge alt sınırı, gerekçe, inceleyen, zaman ve olay geçmişi korunur. **Yeniden migrate etmek eski izinleri veya belge istisnasını geri getirmez.** Eski sürüm belge adedini yeniden zorunlu tuttuğu için istisnadan yararlanan bazı üyelerin katılım kapısı kapanabilir. Aynı yetki/istisna durumuyla geri dönüş DB yedeği gerektirir; yalnız dosya yedeği yeterli değildir. Denetim kayıtlarını korumak için izinlerin yarışma/EYS bağlantılarında ve onayın izin bağlantısında silme kısıtları vardır; operasyonel hesap kapatma pasifleştirme sürecini kullanmalıdır.

Üretim veritabanına, eski kaynaklara veya gerçek kullanıcı hesaplarına yazılmadı. Canlı dağıtım/aktarım yapılmadı.

## Doğrulama

16 özellik testi, 2 bağımsız MariaDB süreç testi ve 1 MariaDB migration testi eklendi. Özel izin ayrımı, kapsam, gerekçe, tekrar/stale istek, üye araması, sıra numarası, onay kaynağı, belge karantinası/bütünlüğü, düzeltme, pasif hesap ve tarih/yayın sınırı, rollback ve denetim hatasında atomik geri alma doğrulandı. Temsilci akışı özellik testinde; EYS/kurum/üye akışı gerçek tarayıcıda doğrulandı.

**Tam SQLite: 840 geçti / 3.867 assertion, MariaDB’ye özel 39 atlama. Tam MariaDB: 879 geçti / 4.305 assertion.** Pint 647 PHP dosyasında, Vite derlemesi ve diff kontrolü geçti. SQLite tam koşusundan sonra yapılan üye açıklaması değişikliği ayrıca odaklı testte doğrulandı; MariaDB tam koşusu son değişikliği içerir.

Tam test ve biçim/derleme sonuçları [kabul kanıtında](2026-09-05-registration-exceptions-evidence.txt), ilgili dosyaların içerik özeti [kaynak manifestinde](2026-09-05-registration-exceptions-source-sha256.json) bulunur. Qpdf/ClamAV tam koşularda etkin; antivirüs yalnız zararsız sentetik test imzalarıyla çalıştırıldı. Üretim virüs tanımı kurulmuş sayılmaz.

TR/EN, 1440/390 px, altı ekran görüntüsü ve iki toplu görsel inceleme. İkinci inceleme sonunda taşma, sayfa hatası veya ilgili konsol hatası yok; erişimi kaldırılan görevlinin beklenen 404 yanıtı hata kabul edilmedi. Üyeye önceki genel belge zorunluluğunu gösteren metin ilk incelemede düzeltildi. Tarayıcı eylemleri gerçek form/oturum/CSRF akışıyla yürütüldü.

## Kalan işler

Kurum görevlisi/sekreterya ve diğer rol ayrımları, olay geçmişinin kapsamlı kullanıcı ekranı, bildirimler, ayrı ön kayıt takvimi kararı, katılım iptali, arşiv düzeltme/aktarım, yük testleri, üretim kurulumu ve pilot kabul açık. Bu paket PK-05/PK-07'nin veya genel yeniden geliştirme hedefinin tamamlandığı anlamına gelmez.
