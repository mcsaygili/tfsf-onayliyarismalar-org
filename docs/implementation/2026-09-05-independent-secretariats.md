# Kurumdan bağımsız sekreterya hesapları ve yarışma ataması

On dokuzuncu yerel uygulama paketi, PK-02.03 / PK-07.02 için eski sekreterya hesap türünü ve yarışma bazındaki operasyon kapsamını geri getirir. Kurum personeli ile sekreterya aynı giriş panelini kullanır; kurum sahipliği ve izinleri aynı değildir.

## Kaynak karşılaştırması

Eski `ThirdParty/Administrator/Models/Kurum/SekreteryaModel.php:90`, `kurum_gorevli` tablosunda `kullanici_turu=2` hesabı oluşturur ve kurum bağlantısı yazmaz. `sekreteryaSilinmeyeUygunMu` yarışmanın `sekreterya_uuid` alanındaki kullanımı denetler. `ThirdParty/Kurum/Models/KurumAktifYarismaModel.php:16` yarışmaları bu atamayla süzer. Aynı kurum portalındaki hesap ekranları kullanıcı türünü ayırır. Yeni projenin ilk `create_institution_staff_table` migration açıklaması bu ayrımı kaldırıp kurumu zorunlu tutmuştu; bu durum eski bağımsız sekreteryanın birden fazla kurumun yarışmasına atanmasını karşılamıyordu.

Bu paket kurumdan bağımsız hesabı geri getirir. Eski portalın bütün rapor/istatistik/sonuç bildirgesi ekranlarını tamamlanmış saymaz; bunlar sonraki PK-07/PK-10 işlerinde ayrıca karşılanmalıdır.

## Hesap ve EYS işlemleri

`institution_staff.account_kind` iki değere sahiptir: `institution` ve `secretariat`. Kurum personelinde `institution_id` dolu, sekreteryada null olmalıdır. Model kaydında bu eşleşme doğrulanır; var olan hesabın türü sıradan güncellemeyle değiştirilemez. Yeni tür genel mass assignment listesine eklenmedi. EYS'nin ayrı servisi sekreterya türünü ve null kurum değerini kendisi belirler. Halka açık kayıt mevcut davranışla yeni kurum ve kurum personeli oluşturur.

EYS'de **Sekreterya hesapları** listesi, oluşturma ve düzenleme ekranları bulunur. `institution.secretariats.manage` izni gerekir; genel yarışma yönetim izni tek başına yetmez. Kurum personeli veya sekreterya kendi kendine bu izni veremez. Hesap pasifleştirme düzenleme ekranındadır; sekreterya için fiziksel silme uç noktası eklenmedi. Mevcut `ModuleRoleSeeder` çalıştırılırsa yeni izin Institution `admin` rolüne de atanır; gerçek ortamda seeder çalıştırılmadı.

İlk parola hashlenir. Hesap başlangıçta e-posta doğrulanmamış durumdadır; sahibi Kurum Portalı'na giriş yapıp mevcut imzalı e-posta doğrulama akışını tamamlar. Yarışmaya atanmak için doğrulama zorunludur. Yeni bir açık parola e-postası veya farklı kurtarma tablosu oluşturulmadı. Mevcut kurum parola yenileme ve kurtarma yolları ortak hesap modeliyle kullanılır. Davet/bildirim teslimatının işletim kurulumu bu paketin kanıtı değildir.

EYS güncellemesi imzalı form bağlamıyla mevcut ad, soyad, e-posta, telefon, aktiflik ve güvenlik damgasına bağlıdır. İki eski form birbirini sessizce ezmez. E-posta değişince doğrulama kaldırılır; güvenlik damgası eski oturumları ve kurtarma bağlamını geçersizleştirir. Sekreteryanın kendi profilinde yalnız ad, soyad ve telefon düzenlenir. Hesap türü, kurum, e-posta veya durum alanı ekleyerek yükselme yapılamaz. Telefon değişiminde mevcut oturum yeniden damgalanır, diğer eski oturumlar geçersiz kalır.

Oluşturma/güncelleme/profil işlemleri `secretariat_account_events` içinde hedef hesap, aktör türü/kimliği, işlem, zaman ve alan değişiklikleriyle transaction içinde kayıtlıdır. Parola veya kurtarma tokenı denetime yazılmaz.

## Yarışma ataması

Yarışmaya `secretariat_id` ve `secretariat_version` eklendi. Bir yarışmanın en fazla bir sekreteryası vardır; bir sekreterya farklı kurumların birden fazla yarışmasına atanabilir. Atama ekranı hem yarışma yönetim hem sekreterya yönetim izniyle açılır. Yalnız aktif ve e-postası doğrulanmış bağımsız sekreterya seçilebilir; normal kurum personeli bu alana atanamaz.

Atama, değiştirme ve kaldırma 10–2000 karakter gerekçe ve güncel sürüm gerektirir. Yarışma mutex'i transaction'ın ilk kilididir. Hedef hesabın durumu kilit altında yeniden okunur. `CompetitionStatusLog` önceki/yeni hesap, sürüm, EYS aktörü, gerekçe ve zamanı saklar. Denetim yazılamazsa atama da geri alınır.

Atanan kişi değiştiğinde önceki sekreteryanın yarışmaya özel aktif doğrudan ön kayıt izni kapatılır ve izin sürümü artırılır; yeni kişiye bu özel izin aktarılmaz. Eski kişi tekrar atanırsa ayrıca yeniden izin verilmesi gerekir. Daha önce geçerli şekilde verilmiş katılımcı onayları silinmez. Atama kaldırma ve ön kayıt onayı aynı kilidi paylaşır: hangisi önce tamamlanırsa sonraki işlem o güncel kapsamı görür.

## Operasyon kapsamı

`InstitutionCompetitionAccess` hem sorgu kapsamının hem tekil kayıt erişiminin ortak kaynağıdır:

| İşlem | Kurum personeli | Bağımsız sekreterya |
|---|---|---|
| Kurum profili/personeli/başvuru sihirbazı | Mevcut kurum kapsamı | Erişemez |
| Çalışma alanı | Kendi kurumu | Yalnız atanmış yarışmalar |
| Ön kayıt listesi/inceleme/belge | Kendi kurumu ve kurum inceleme türü | Atanmış yarışma ve kurum inceleme türü |
| Fotoğraf katılım onayı/güvenli görsel | Kendi kurumu | Atanmış yarışma |
| Doğrudan ön kayıt/istisna | Ayrıca yarışmaya özel izin gerekir | Ayrıca yarışmaya özel izin gerekir |
| Kendi parola/profil işlemleri | Mevcut akış | Aynı parola güvenliği, ayrı sınırlı profil |

Kurum pasifleşince o kurumun yarışmaları sekreteryanın operasyon kapsamından çıkar; başka aktif kurumlara ait atamaları etkilenmez. Sekreterya hesabı pasifleşince bütün panel erişimi kapanır. İstemci tarafındaki menü saklama yeterli sayılmadı: `RestrictSecretariatRoutes`, sekreterya için izin verilen rota aileleri dışındaki doğrulanmış kurum yönetim yollarını sunucuda reddeder. Yeni bir kurum rotası kendiliğinden sekreterya erişimi kazanmaz.

Ön kayıt belgesi indirme mevcut özel/taranmış dosya politikasını kullanır. Fotoğraf erişimi mevcut güvenli jüri türevi üzerinden yapılır; yeni kapsam dışında kalan sunucu isteği reddedilir. Önceden indirilmiş dosyanın alıcının cihazından geri alınabildiği iddia edilmez.

Fotoğraf onay servisi daha önce yalnız HTTP katmanında denetlenen aktör yetkisini artık yarışma kilidi alındıktan sonra tekrar denetler. Böylece eski atamayla açılmış form veya doğrudan servis çağrısı, görev kaldırıldıktan sonra karar yazamaz. Mevcut jüri formu/sonuç güncelliği ve final sınırı korunur.

## Geçiş, oturum ve geri dönüş

Migration: `2026_09_05_280000_restore_independent_secretariat_accounts.php`. Mevcut kuruma bağlı hesaplar `institution` kalır; otomatik sekreterya hesabı veya yarışma ataması oluşturulmaz. Eski tür 2 hesapları kurum uydurulmadan yeni bağımsız türle; eski `sekreterya_uuid` ilişkileri açık UUID eşleme tablosuyla aktarılmalıdır. Gerçek eski veri aktarımı yapılmadı.

Hesap türü oturum/kurtarma fingerprint'ine eklendi. **Mevcut kurum paneli oturumları ve daha önceki kurtarma bağlamları geçişte yenilenmelidir.** Tür dönüşümü veya veri aktarımı, sıradan form güncellemesi gibi uygulanmamalıdır.

Rollback yarışma atama alanlarını, tür alanını ve yeni hesap olay tablosunu kaldırır. **Bağımsız hesapları silmez veya sahte kuruma bağlamaz; `institution_id` nullable kalır.** Eski uygulama bu kurumsuz hesapların girişini reddeder. Yeniden migration null kurumluları sekreterya olarak tanır, ancak atamaları ve hesap olaylarını geri getirmez. Yarışmanın genel atama denetimleri `competition_status_logs` içinde korunur. Aynı atama ve tam hesap olay geçmişiyle geri dönmek için DB yedeği gerekir. Bu geri dönüş orijinal NOT NULL şemaya birebir dönüş değildir; veri korumak için bilinçli olarak nullable alan bırakılır.

Gerçek uygulama DB'sinde migration, seeder, hesap/izin ataması veya dağıtım yapılmadı. Eski kaynaklara yazılmadı.

## Kabul kanıtı ve kalan kapsam

14 yeni özellik testi, 2 bağımsız MariaDB süreç yarışı ve 1 MariaDB migration testi eklendi. Gerçek giriş/pasifleştirme, bağımsız/çok kurumlu kapsam, ayrı EYS izni, kayıt/onay/belge erişimi, yarışma sihirbazına erişim reddi, atama revizyonu, izin devrinin engellenmesi, email/profil bağlamı ve hata halinde atomik geri alma denetlendi.

Tarayıcıda EYS hesap oluşturma → sekreterya girişi → geçerli imzalı e-posta doğrulama → yarışma ataması → ön kayıt onayı → kendi profilini güncelleme → atamayı kaldırınca erişim reddi doğrulandı. TR/EN, 1440/390 px ve dört ekran görüntüsü. İlk denemede testin Türkçe varsayımı İngilizce başlayan yeni oturumla uyuşmadı; test dil seçimini açık yaptı. Uygulama davranışı bu hatayı örtmek için değiştirilmedi. Son akış geçti.

**Tam SQLite: 854 geçti / 3.939 assertion, MariaDB’ye özel 42 atlama. Tam MariaDB: 896 geçti / 4.404 assertion.** İki koşuda da gerçek qpdf/ClamAV araç testleri sentetik antivirüs imzalarıyla etkin. Odaklı SQLite 14 test / 70 assertion, odaklı MariaDB 17 test / 99 assertion geçti. Pint 658 PHP dosyasında, Vite ve diff kontrolü başarılı.

[Tam test çıktıları](2026-09-05-independent-secretariats-evidence.txt) · [Kaynak içerik manifesti](2026-09-05-independent-secretariats-source-sha256.json).

Kurum personeli içindeki daha ayrıntılı izinler, sekreteryanın tam yarışma detay/istatistik/rapor/sonuç arşivi ekranları, kapsamlı kullanıcı olay ekranı, bildirim teslimatı, eski kayıt aktarımı, üretim geçişi ve pilot kabul açık. Genel hedef ve PK-02/PK-07 bütünü tamamlanmış değildir.
