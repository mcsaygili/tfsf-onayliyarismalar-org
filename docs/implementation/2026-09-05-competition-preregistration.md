# Yarışma ön kaydı ve sürümlü belgeler

On beşinci yerel uygulama paketi. PK-05.01–05.04 ile PK-07.02'nin ön kayıt alt kapsamını uygular; PK-05'in bütün istisna, doğrudan kayıt, aktarım ve operasyon işleri tamamlanmış değildir.

## Eski sistemden korunan ayrım

Eski `ThirdParty/Frontend/Controllers/Yarisma/YarismaKayitOl.php` belge onaylı yarışmada 1–3 PDF ister. `ThirdParty/Frontend/Models/Yarisma/YarismaKayitOlModel.php` ayrı `yarisma_katilim_kayitli_kullanicilar` kaydı, yarışma bazlı sıra numarası, onay tarihi ve `yarisma_katilim_kayit_belge` eşlemeleri kullanır. Eski sıra üretimi `MAX+1`; belge taşıması istemciden gelen geçici dosya adına dayanır.

Yeni sistemde daha önce yalnız fotoğraflar gönderildikten sonra kategori başvurusu onayı vardı. Ön kayıt artık ayrı bir varlıktır. Üyenin genel `uye_turu` değiştirilmez; bir yarışmaya kabul başka yarışmaya kabul sayılmaz. Mevcut fotoğraf başvurusu incelemesi ayrı çalışmaya devam eder.

## Yapılandırma ve yaşam döngüsü

Kurum sihirbazının 4. adımında ön kayıt zorunluluğu, en az belge sayısı (0–3) ve kurum/atanmış temsilci inceleyicisi seçilir. Üst sınır 3, belge başına sınır 10 MB'dir. Sıfır belge, belgesiz ön kayıt onayını destekler; belge yüklemek isteğe bağlı kalır. Ön kayıt başvuru takvimini kullanır. Ayrı ön kayıt tarih aralığı henüz yoktur.

Yeni alanlar varsayılan olarak kapalıdır; eski canlı yarışmalara otomatik yeni zorunluluk getirilmez. İlk kayıt oluşturulduğunda inceleyici türü ve belge koşulu kayda alınır. Kayıt oluşmuş yarışmada sihirbazdan bu kurallar değiştirilemez. Şartname bağlamında `registration_required`, `registration_document_min`, `registration_reviewer` alanları vardır; mevcut şartname şablonlarını bu alanlarla yayına hazırlama işi ayrıca izlenmelidir.

Üye yarışma sayfasından ön kaydını açar. Oluşturma yarışma satırı kilidi altında, monoton sayaç ve iki DB tekilliğiyle yapılır: yarışma+üye, yarışma+kayıt numarası. Tekrarlı istek aynı kaydı getirir ve ikinci olay üretmez.

Akış: taslak → inceleme bekliyor → onay / ret / düzeltme. Düzeltmede üye belgeyi değiştirip aynı kayıt numarasıyla yeniden gönderir. Ret son durumdur; yeniden incelemeye açık bir işlem için inceleyici “düzeltme iste” seçmelidir. Üye ve inceleyici formları sürüm taşır; her belge veya durum değişimi sürümü artırır. Eski form hata verir, güncel kararı ezmez. İnceleyici kararını açıkça seçer; onay varsayılan seçilmez.

Oluşturma, yükleme, kaldırma, gönderim ve karar olayları; aktör türü/kimliği, sürüm, karar gerekçesi ve incelenen belge kimlikleriyle kaydedilir. Karar veren ve tarih kayıt üzerinde de tutulur. Önceki karar notları olay tablosunda korunur; ekran mevcut karar gerekçesini ve eski belge sürümlerini gösterir. Ayrıntılı olay geçmişi ekranı bu paketin dışında kalır.

## Belge ve erişim sınırları

Dosya istemciden gelen yol veya geçici dosya adıyla alınmaz; doğrudan dosya yüklemesi doğrulanır. Gerçek MIME, byte sınırı ve PDF başlık/son işaretleri denetlenir. Aynı belgenin farklı sıraya ikinci kez eklenmesi engellenir. Yeni dosya rastgele adla özel diske yazılır; dosya yolu dışarıdan seçilemez.

Her sıranın ayrı artan belge sürümü vardır. Değiştirilen veya kaldırılan dosya geçmiş olarak tutulur. Güncel dosyaları değiştirip yeniden göndermek eski inceleyici formunu geçersiz kılar. Gönderim ve onayda gerekli belge sayısı, dosyanın varlığı ve SHA-256 bütünlüğü yeniden kontrol edilir.

Dosyalar yalnız sahibi ve kaydın yetkili inceleyicisi için açılır. Kurum inceleyicisi aynı kurumdan olmalı; temsilci yarışmanın güncel atanmış temsilcisi olmalıdır. Taslak ilk kez gönderilmeden inceleyici belgeyi göremez. Başka üye/kurum veya ataması kaldırılmış temsilci 404 alır. Jüri/kamu belge adresi yoktur.

İndirme, aynı açık dosya akışının checksum doğrulamasını kullanır. Yanıt `attachment`, `application/octet-stream`, `nosniff`, `private, no-store` ve `Content-Security-Policy: sandbox` başlıkları taşır. Başvuru numarası ve belge sürümünden üretilen dosya adı kullanılır.

**Sınır:** MIME/PDF işareti denetimi tam PDF yapısal çözümleme, zararlı yazılım taraması veya aktif içerik temizleme değildir. Şifreli/bozuk/aktif içerikli PDF politikası ve tarama/karantina altyapısı tamamlanmadan bu paket belgeler için bütün güvenlik kabulünü sağlamaz. Görüntüleyiciye güvenli PDF üretildiği iddia edilmez. Sunucu belgeyi PDF motorunda çalıştırmaz; yetkili kullanıcıya indirme olarak verir.

## Fotoğraf başvurusu ve atomiklik

Ön kayıt isteyen yarışmada onay olmadan uygunluk başarısız olur; fotoğraf başvurusu başlatma/gönderme engellenir. Mevcut taslağa fotoğraf/portföy ekleme ve beyan değişikliği de aynı kontrolü kullanır. Böylece önceden açılmış taslak veya doğrudan HTTP isteği onayı atlayamaz.

Henüz fotoğraf başvurusu gönderilmemiş onay, gerekçeyle düzeltmeye alınabilir. Gönderilmiş fotoğraf başvurusunun kullandığı onay sessizce kaldırılamaz; önce açık katılım iptal süreci gerekir. Final veya sonuç yayınından sonra ön kayıt kararı değiştirilemez. Bu, genel yetkili iptal/istisna işinin tamamlandığı anlamına gelmez.

Bütün ön kayıt yazmaları yarışma kilidi ve transaction içinde çalışır; fotoğraf yazmaları/gönderimiyle kilit ortaklaşır. Olay kaydı hatasında belge satırı, sürüm ve yeni dosya geri alınır. Eski dosya korunur. Ani süreç kesintisinde orphan dosya uzlaştırması, belge saklama süresi ve disk kotası politikası ayrıca tamamlanmalıdır.

## Geçiş

`2026_09_05_240000_create_competition_registrations.php`: yarışmaya dört alan; ön kayıt, belge sürümü ve olay tabloları. Yeni kayıtlar mevcut kullanıcı/yarışmayı fiziksel silmeye karşı `RESTRICT` kullanır. Kişisel veri saklama/maskeleme ve kontrollü silme süreci ayrı geliştirilmelidir.

Eski kayıt aktarımında yarışma/üye/sıra numarası tekilliği, belge sahipliği, mevcut dosya/hash, onay tarihi ve kaynak durumu doğrulanmalı; yarışma sayacı aktarılan en büyük numaraya ayarlanmalıdır. Eski genel kullanıcı türü 3, ön kayıt yerine kullanılamaz. Gerçek migration veya veri aktarımı çalıştırılmadı.

**Rollback veriyi kaldırır:** yeni tablolar ve yarışma alanları silinir; özel belge dosyaları otomatik silinmez. Tekrar migrate eski ön kayıtları/kararları geri getirmez ve ön kayıt zorunluluğu varsayılan kapalı olur. Üretimde kullanılmadan önce DB ve özel dosya yedeği, bakım penceresi ve geri dönüşte katılım engelinin korunması birlikte planlanmalıdır. İzole `tfsf_results_ui` şemasında rollback/yeniden uygulama kontrolü, diğer yarışma verilerinin değişmediğini ve özel dosyaların kaldığını doğruladı.

## Doğrulama

16 yeni özellik testi; 3 bağımsız MariaDB süreç testi. Sahiplik/inceleyici kapsamı, taslak mahremiyeti, yanlış yol/MIME/sıra/boyut, minimum belge, sürüm, düzeltme/geçmiş, bozuk dosya, olay hatası rollback'i, ret gerekçesi, tarih/görünürlük, yapılandırma kilidi ve fotoğraf başvurusu bağlantısı test edildi. Süreç testleri kayıt numarası tekilliğini, çift isteğin aynı kaydı döndürmesini ve iki kararın birbirini ezmemesini doğrular.

16 özellik ve 3 MariaDB süreç testi eklendi. Tam SQLite: **792 geçti / 3.599 assertion** (34 MariaDB atlaması). Tam MariaDB: **826 geçti / 3.983 assertion**. Pint **623 PHP dosyasında**, Vite derlemesi, `git diff --check` ve izole migration geri alma/yeniden uygulama başarılı. Odak grup 16 test / 112 assertion; süreç grubu 3 test / 32 assertion geçti.

Chrome'da gerçek üye ve kurum girişleriyle ön kayıt oluşturma, PDF yükleme/aynı byte'ı indirme, gönderim, düzeltme, belge sürüm 2, eski sürümü görme, onay sonrası fotoğraf kapısının açılması ve sihirbaz ayar kaydı geçti. TR/EN ile masaüstü/mobil kontrol edildi. İki toplu görsel inceleme yapıldı. Yalnız sentetik veri, ayrı storage ve `array` posta sürücüsü; dışarıya mesaj gönderilmedi.

[Doğrulama çıktıları](2026-09-05-competition-preregistration-evidence.txt) · [Kaynak manifesti](2026-09-05-competition-preregistration-source-sha256.json).

Özel yetkiyle doğrudan kayıt/istisna, sekreterya–görevli yetki farkları, ayrı ön kayıt takvimi, bildirim tercihleri, olay geçmişi ekranı, PDF tarama/karantina, eski kayıt aktarımı, seri/grup, bütün raporlar ve pilot kabul açık. Genel hedef aktiftir.
