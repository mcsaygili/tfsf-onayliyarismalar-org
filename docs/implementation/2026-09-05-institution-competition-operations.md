# Kurum ve sekreterya yarışma operasyonları

Yirmi birinci yerel uygulama paketi, PK-07.01 için yarışma operasyon listesi, salt okunur yarışma ayrıntısı, katılımcılar ve güncel kategori/durum sayılarını ekler. Kurum yarışma hazırlama sihirbazı ile bağımsız sekreteryanın operasyon yetkisi ayrıdır.

## Eski davranış ve yeni karşılık

Eski `ThirdParty/Kurum/Controllers/AktifYarismaBilgileri.php:22–140` sekreteryaya atanmış yarışma listesi, ayrıntı, kişi bazında gruplanmış kategori katılımları ve istatistikleri sunuyordu. `ArsivYarismaBilgileri.php:21–146` arşiv karşılıkları yanında ülke, şehir, cinsiyet ve yaş kırılımlarını da içeriyordu. `YarismaIstatistikSharedModel.php` bazı “katılımcı toplamı” alanlarında kategori katılım satırlarını sayıyordu; yeni kişi toplamı bu davranışı tekrar etmez. Kategori başvurusu toplamı ayrıca gösterilir.

Yeni `institution.operations.index` (`/operasyonlar`) ve `institution.operations.show` (`/operasyonlar/{competition}`) kurum personeli ve bağımsız sekreterya için ortaktır. Menü ve sekreterya çalışma alanından ulaşılır. Kurum personeli yalnız kendi kurumunun, sekreterya yalnız atandığı aktif kurumların yarışmalarını görür. `InstitutionCompetitionAccess` liste ve tekil sorguyu sınırlar. Başka yarışma kimliği 404 verir; atama kaldırıldığında detay erişimi de kalkar. Yeni yollar sekreterya rota izin listesine açıkça eklendi; kurum/personel yönetimi ve sihirbaz yetkisi genişlemedi.

Liste 20, katılımcılar 25 kayıtla sayfalanır. Sonucu henüz yayımlanmayan, sonucu yayımlanan ve iptal edilen görünümler vardır. **Başvuru süresinin dolması yarışmayı arşive taşımaz.** Başvuru onay durumu ve kamu yayın durumu ayrı görünür. Henüz gerçek bir arşiv yaşam döngüsü oluşturulmadığı için sonuç yayımlanmış yarışmaları tarihsel arşivmiş gibi etiketleyen bir alan eklenmedi.

Detayda yarışma adı, kurum, temsilci adı, başvuru/değerlendirme tarihleri, onay/yayın durumları, kategori ve durum filtreleri, katılım özeti ve kişi listesi bulunur. Ön kayıt ve fotoğraf onay bağlantıları mevcut yetkili listeleri açar; bu bağlantıların yarışmaya özel filtre sunduğu iddia edilmez.

## Sayıların anlamı

`InstitutionCompetitionOperations` ortak sorgu sözleşmesini uygular. Hem yarışma katılımının hem kategori başvurusunun `submitted_at` değeri dolu olmalıdır. Taslak oluşturmak katılımcı sayısını artırmaz. Reddedilen, çekilen ve diskalifiye olmuş gönderilmiş kayıtlar durumlarıyla korunur; varsayılan toplam “uygun katılımcı” veya “ödül” toplamı değildir.

- **Katılımcılar:** filtreye uyan kategori başvurularındaki benzersiz yarışma katılımı/kişi. Aynı kişi iki kategoriye katılsa da bir kez sayılır.
- **Kategori başvuruları:** filtreye uyan gönderilmiş kategori kayıtları. Kategori/durum tablosunda ayrı gösterilir.
- **Çekilmemiş fotoğraflar:** bu kategori kayıtlarının `withdrawn_at` alanı boş fotoğrafları. Geri çekilmiş fotoğraf dosyası toplamda yoktur; reddedilmiş başvuruda kalan dosya toplamda olabilir.

Kişi listesi, kategori satırları ve özet aynı filtreleri kullanır. Sayfa numarası toplamı değiştirmez. Yarışma dışından kategori seçilemez. Geçersiz kategori/durum/görünüm/sayfa doğrulama hatası üretir. Hiç başvurusu olmayan kategoriler sıfırla görünür. Çeviriler toplam sorgularına join edilmediğinden dil sayısı katılımı çoğaltmaz. Kategori içindeki tek katılım ilişkisi mevcut benzersiz kayıt kuralına dayanır.

Detay verileri tek okuma transaction'ında hazırlanır; kullanılan MariaDB test ortamının tekrarlanabilir okuma davranışı korunur. Bu sayfa karar kilidi almaz ve kayıt değiştirmez. Farklı üretim isolation ayarları altında aynı snapshot davranışının ayrıca değerlendirilmesi gerekir.

## Veri minimizasyonu ve performans

Kişiler için yalnız kimlik anahtarı, ad/soyad ve ülke/şehir anahtarları yüklenir. E-posta, telefon, kimlik numarası, parola, doğum tarihi ve tercihler katılımcı sorgusuna alınmaz. Fotoğraf dosya yolu, dosya adı veya fotoğraf önizlemesi ekrana taşınmaz. Tüm adlar Blade ile kaçışlı gösterilir.

`User` üzerinde salt ilişki yöntemleri olarak ülke/şehir eklendi; silinmiş referansın adını göstermeyi sürdüren `withTrashed` kullanılır. Eksik ilişki açıklayıcı boş değer gösterir. Kişi adı ve yer bilgisinin **güncel profilden** geldiği ekranda yazılıdır; tarihsel demografi olduğu iddia edilmez.

Sayfalama, ilişkileri toplu yükleme ve SQL toplama kullanılır. Test, kişi sayısı 1'den 25'e çıktığında kişi sayfasının sorgu sayısının artmadığını doğrular. Bu sorgu sayısı testi gerçek üretim hacminde süre/bellek ölçümü yerine geçmez.

## Doğrulama

11 yeni özellik testi: kurum/sekreterya kapsamı, atama kaldırma, pasif kurum, kişi/kategori/fotoğraf ayrımı, filtreler, taslak/çekilmiş durumları, yabancı kategori/bozuk filtreler, sayfalama, hassas alanların yüklenmemesi, çıktı kaçışı, TR/EN, silinmiş referans ve sorgu sayısı. Son odaklı SQLite **11 test / 51 assertion** geçti. Önceki odaklı MariaDB **23 test / 116 assertion** yeni ilk 9 testi ve mevcut sekreterya paketini kapsadı; son iki test tam koşuda da doğrulanacak.

Gerçek Chrome akışı: sekreterya girişi → kapsamlı liste → yarışma detayı → kategori/red filtresi → temizleme → ikinci sayfa → İngilizce boş durum → mobil menü → başka yarışmada 404. Sentetik 28 kişi, 29 kategori başvurusu ve 2 fotoğraf toplamları gerçek DOM'da doğrulandı. 1440×1050 ve 390×1050, dört ekran görüntüsü, uygulama konsol hatası yok. İlk görsel turda viewport değişimi sırasında açılır menü animasyonu bitmeden ekran alınmıştı; ikinci tur menünün kapanmış konumunu bekler ve ilk görünür ekranı kaydeder. İkinci tur geçti; uygulama kodu bu test zamanlamasını örtmek için değiştirilmedi.

Vite build geçti. Mekanik tasarım taraması yeni operasyon alanında bulgu üretmedi; mevcut `app.css:183` portföy kartı dekorasyonu için kapsam dışı uyarı verdi. Genel temayı yeniden tasarlamak bu paket kapsamında değildir. Tam SQLite/MariaDB ve son Pint sonuçları tamamlanınca bu kayda eklenecek.

## Kalan kapsam ve geçiş

Migration veya toplu veri güncellemesi yoktur. Eski kaynak, gerçek veritabanı, gerçek hesaplar ve üretim dağıtımı değiştirilmedi. Test bildirimleri sentetik ortamda kalır.

**PK-07.01 bütünü tamamlanmadı:** tarihsel demografik yakalama, cinsiyet/ülke/şehir/yaş kırılımları, arşiv yaşam döngüsü ve arşiv katılımcı kayıtları açık. Eski kayıtta bulunmayan tarihsel alan bugünkü profille uydurulmayacak. Sonraki iş, katılım/yayın anında tarihsel veri sözleşmesini ve mevcut kayıtların “bilinmiyor” davranışını kurmaktır. PK-07.03 ödüller, güvenli fotoğraflar, sonuç bildirgesi; rapor/ZIP; eski aktarım; pilot ve üretim geçişi de açık. Genel özellik eşdeğerliği hedefi aktiftir.
