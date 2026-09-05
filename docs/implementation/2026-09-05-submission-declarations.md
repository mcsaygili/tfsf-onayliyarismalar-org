# Fotoğraf beyanları, hikâyeler ve sıra — sekizinci uygulama paketi

5 Eylül 2026 · PK-06.03 ve PK-06.05'in beyan düzenleme alt kapsamı uygulandı. PK-06.04'ün bağımsız seri/grup kimliği, eski veri aktarımı ve pilot kabul hâlâ açık.

## Davranış

Fotoğraf adı, çekim yeri ve beyan edilen çekim tarihi artık yarışma kopyasında düzenlenebilir. Kurum kategori adımında fotoğraf hikâyesi, ortak kategori hikâyesi ve katılımcının belirleyeceği fotoğraf sırasını seçer. Bayraklar kategori çoğaltma ve kaydet/aç akışında korunur; eski payload'da bulunmamaları mevcut ayarı sıfırlamaz.

Üye önce fotoğrafları ekleyip bilgileri taslak olarak saklayabilir. Eksik ad, yer veya tarih kesin gönderimi engeller. Kategori istiyorsa fotoğraf/kategori hikâyesi de zorunludur. Açıklamalar en çok 4.000 karakter, ad ve yer 255 karakterdir. Tarih kesin `Y-m-d` biçiminde ve geçerli takvim tarihi olmalıdır; belirsiz tarih dönüşümü yapılmaz. Metinler düz yazı olarak saklanır ve Blade ile kaçışlanır.

Portföydeki **kullanıcı beyanı** başvuruya kopyalanır. Beyan tarihi EXIF tarihinden üretilmez; dosyanın `exif_captured_at` ve diğer ham metadata'sı ayrı `metadata_snapshot` içinde kalır. Yarışma kopyasını düzenlemek portföyü değiştirmez; portföyü değiştirmek/silmek yarışma kopyasını değiştirmez. Yeni doğrudan yüklemede beyan alanları vardır; taslakta sonra tamamlanabilir, değerlendirme dönemindeki eklemede zorunlu bilgiler eksik olamaz.

Kategori sırası istendiğinde kullanıcı her fotoğrafa 1…N arasında benzersiz sıra verir; tam liste tek transaction içinde kaydedilir. DB'deki `sort_order` 10'un katlarıyla saklanır. Aynı kategorideki jüri listesi kayıtlı sırayı kullanır; bu özellik bağımsız seri/grup modelinin yerine geçmez.

## Güvenlik ve tutarlılık

- `PUT basvuru/{submission}/bilgiler` sahiplik, açık işlem dönemi ve istek sınırı kontrollerini uygular. Servis de kilit altında başvuru sahibini doğrular.
- Düzenleme, fotoğraf ekleme/silme ve kesin gönderim aynı başvuru satırı kilidini kullanır. Formun `details_version` değeri güncel olmalı ve gönderilen fotoğraf UUID kümesi mevcut aktif fotoğraf kümesiyle tam eşleşmelidir.
- Fotoğraf ekleme, silme, geri çekme/geri alma veya beyan düzenleme form sürümünü artırır. Fotoğraf ekleyip sonra silerek önceki kümeye dönmek eski formu yeniden geçerli kılmaz.
- Eski form yeni veriyi ezmez. Tarayıcı hatayı açıklar ve kullanıcının yazdığını korur; kullanıcı metnini kopyalayıp güncel sayfayı açabilir.
- Kesin gönderim, bütün kategorilerin beyanlarını kilit içinde tekrar denetler. İlk kategorinin durumu güncellendikten sonra başka kategoride hata oluşursa tüm transaction geri alınır.
- Normal gönderimden sonra bilgiler kilitlidir. Mevcut revizyon politikasının izin verdiği değerlendirme döneminde tam beyanla düzenlenebilir; jüri tamamlama kaydı yeniden açılır, bildirim commit sonrasına bırakılır ve sürüm numarasıyla olay kaydedilir. Olay kaydı hatasında beyan, sıra, kategori hikâyesi ve sürüm geri alınır.
- Jüri ekranına yalnız kategori tarafından istenen fotoğraf/kategori hikâyeleri eklendi. Ham EXIF, dosya adı, beyan tarihi/yeri veya hesap kimliği bu eklemede gösterilmez. Metnin kendisinde kullanıcının kimliğini yazmasını otomatik tespit eden bir mekanizma yoktur; form kimlik/imza/iletişim bilgisi yazılmamasını açıklar. Su işareti/metin incelemesi ve tam anonimlik politikası ayrı işletim işidir.

## Eski sistem karşılığı

Kaynak `ThirdParty/Frontend/Models/Yarisma/YarismaKayitModel.php:20–43` ve `ThirdParty/Frontend/Views/Yarisma/yarismaKayitView.php:828–865`: `fotograf_adi`, `cekildigi_yer`, `cekildigi_tarih`, `fotograf_sirasi`, `fotograf_hikayesi`, `kategori_hikayesi`. Eski tarih alanı 10 karakterlik gün/ay/yıl girdisi kullanıyordu; model `strtotime` ile sessiz dönüşüm yapıyordu. Yeni akışta geçersiz gün/ay normalize edilerek kabul edilmez.

| Eski alan | Yeni karşılık |
|---|---|
| `fotograf_adi` | Fotoğraf `declaration.title` |
| `cekildigi_yer` | `declaration.location` |
| `cekildigi_tarih` | `declaration.taken_on` — gün hassasiyetinde beyan; EXIF ayrı |
| `fotograf_hikayesi` | `declaration.story`; kategori `photo_story_required` |
| `kategori_hikayesi` | Başvuru `category_story`; kategori `category_story_required` |
| `fotograf_sirasi` | Kategori `photo_order_required` ve fotoğraf `sort_order` |

Eski serilerin tüm grup kimliği/klasör düzeni bu eşlemeyle aktarılmış sayılmaz. Eski DB'de saat içeren gerçek kayıt varsa importer onu kaybetmeden ayrı kaynak alanında korumalı; gün hassasiyetindeki forma sessizce daraltmamalıdır.

## Şema ve geçiş

`2026_09_05_170000_add_submission_declarations.php` üç kategori bayrağı, başvuruda `category_story` / `details_version` ve fotoğrafta nullable `declaration` JSON ekler. Eski fotoğraflarda JSON boşken yalnız mevcut portföy beyan snapshot'ı okunur; EXIF tarihi tahmin yerine kullanılmaz. İlk düzenlemede yarışmaya özel JSON yazılır. Var olan metadata yeniden yazılmaz ve tüm eski kayıtlar için tahmini veri backfill'i yapılmaz.

Eksik bilgili eski taslaklar yeni kesin gönderimde tamamlanmalıdır. Daha önce onaylanmış katılımlar topluca geriye çevrilmez. Hikâye/sıra bayraklarının başlangıcı kapalıdır; eski kategorileri doğru bayraklarla doldurmak PK-14 importer işidir. Migration gerçek veritabanında çalıştırılmadı.

Rollback yeni sütunları ve bu sütunlara kaydedilen beyan/hikâyeleri kaldırır; uygulanmadan önce veri dışa aktarımı/yedeği gerekir. Eski sürüm yeni kesin gönderim kontrollerini ve form sürümünü uygulamaz. Rollback/yeniden migrate yalnız geçici sentetik UI DB'sinde prova edilmiştir.

## Kabul kanıtı

21 yeni senaryo eklendi. Nihai kaynak üzerinde ardışık tam koşular:

| Kontrol | Sonuç |
|---|---|
| SQLite | 708 geçti; MariaDB'ye özel 20 atlama; 2.862 assertion |
| MariaDB 11.8 | 728 geçti; 3.076 assertion |
| Pint | 575 PHP dosyası geçti; son eklenen test de biçimlendirildi |
| Vite derlemesi | Geçti |
| Migration rollback / yeniden migrate | İzole UI DB'sinde geçti |
| `git diff --check` | Geçti |
| Gerçek tarayıcı | Kurum/üye/jüri, masaüstü/mobil, eski sekme, gönderim ve hikâye okuma geçti |

`SubmissionDeclarationsTest` 18, `SubmissionDeclarationsConcurrencyTest` 2 yeni senaryo içerir; kategori bayrakları için mevcut `CategoryPhotoRulesStepTest` sınıfına 1 senaryo eklendi. Önceki hesap, anonim görsel, jüri etiketleri, teknik fotoğraf kuralları ve katılım testleri de tam regresyona dahildir. Son tam SQLite ve MariaDB koşuları paylaşılan test storage'ının çakışmaması için ardışık çalıştırıldı. Yeni senaryolar: beyan–EXIF ayrımı, portföyden bağımsızlık, taslak/son gönderim, zorunlu hikâyeler, geçersiz tarih/tip/uzunluk, yabancı fotoğraf/sahiplik, eski form, ekle–sil sonrası eski sürüm, sıra çakışması, olay hatası rollback, kilitli dönem, değerlendirme revizyonu, hikâyede HTML kaçışlama, değerlendirme sırasında gerçek HTTP yükleme ve kategori bayrakları. İki MariaDB süreç testi eski form yarışı ve eksik beyanla gönderim yarışını kapsar; fixture başvurunun yarış öncesinde geçerli olduğu ayrıca doğrulanır.

[Ham kanıt](2026-09-05-submission-declarations-evidence.txt) · [Kaynak SHA-256 kaydı](2026-09-05-submission-declarations-source-sha256.json) · [Tarayıcı raporu](/Users/mcsaygili/Documents/ChatGPT/TFSF/reports/2026-09-05-beyan-hikaye-sira-qa.md)

## Açık kapsam

1. Jüri puan kaydı/finalize, kurum onayı ve final tur işlemlerinin aynı ortak revizyon kilidi/sürümüne katılması henüz tamamlanmadı. Bu paket iki üye düzenleme/gönderim yarışını çözer; jüri ve üye arasındaki bütün yarışlara karşı değişmez sürüm kabulü vermez.
2. Olayda sürüm numarası var; eski/yeni beyanın bütün içeriğini saklayan değişmez revizyon arşivi ve sonuç yayınına bağlanan snapshot henüz yok. Sonuç kataloğunda beyan/hikâye gösterimi ilgili PK-09 yayın sürümü işine dahil edilmeli.
3. Bağımsız seri/grup kimliği, ek belgeler, geçici/parçalı yükleme ve kesinti onarımı açık. Görsel kabul kontrollerinin form sürümüyle tek değişmez şartnameye bağlanması da sürüyor.
4. Legacy importer, gerçek veri mutabakatı, staging yük/kaynak testleri ve kullanıcı pilotu yapılmadı. Tam proje veya PK-06 bütünü kabul edilmedi.
