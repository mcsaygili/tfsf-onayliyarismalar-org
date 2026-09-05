# Jüri değerlendirme revizyonu ve ortak işlem kilidi

Dokuzuncu yerel uygulama paketi. PK-06.05/06.06 ve PK-08 puanlama/finalizasyon güvenliğinin bir bölümünü tamamlar. Eski/yeni sistemin bütün özelliklerinin kabulü veya üretim dağıtımı değildir.

## Davranış

Jüri formu, gösterilen eserler, beyan sürümleri, kriterler, kategori koşulları, jüri ataması, tur ve yarışma takviminin HMAC özetiyle gönderilir. Atama üzerindeki `evaluation_version` her başarılı puan kaydında artar. İkinci sekme önceki puanları ezemez; katılımcı revizyonundan önce açılmış form eski eser kümesini kesinleştiremez. Hata dönüşünde eski form kanıtı ve kullanıcının girdisi korunur; güncel veriyle devam etmek için sayfa yeniden yüklenir.

Puanların kaydı, tüm aktif eser/kriter çiftlerinin kontrolü, kesinleştirme, puanların gönderim zamanı ve denetim olayı tek transaction içindedir. Eksik/geçersiz puan veya olay kayıt hatası hiçbir kısmi kesinleştirme bırakmaz. Küsuratlı puan ve başka esere/kritere ait değerler sessizce atlanmaz. Tur ve atama kilit sonrası yeniden okunur. Formda gösterilen kriterler de imzalanan güncel veri kümesinden gelir.

Fotoğraf ekleme/silme/geri çekme/geri alma, beyan düzenleme, kategori ekleme ve başvuruyu gönderme işlemleri önce yarışmayı, sonra mevcut başvuru satırını kilitler. Jüri işlemleri ve kurum katılım kararı aynı yarışma kilidini kullanır. Katılım kararı eser kümesini değiştirdiğinde eski jüri tamamlamaları açılır; puanlar taslak olarak kalır ve eski form sürümü geçersizleşir. Final turu başladıktan veya sonuç yayımlandıktan sonra katılım kararı ve birinci tur puan yazması engellenir.

EYS `CompetitionReviewController` üzerindeki yarışma yazma rotaları, güncel yarışmayı transaction içinde yeniden bağlayan middleware kullanır. Sonuç hesaplama, final turu ve sonuç yayını bu kilide katılır. Render edilmiş istisna, HTTP hata yanıtı ve başarısız form dönüşü rollback yapar. Mevcut “jüri kaydı bekleniyor” iş akışı, başarıyla `WaitingRequirements` durumuna geçtiği hâlde arayüzde hata bölgesinde açıklama gösterir; yalnız bu açıkça işaretlenmiş başarılı geçiş commit edilir. İstek gövdesi bu işareti belirleyemez. Sonuç bildirimi commit sonrasına ertelenir.

Değerlendirme ekranını okumak mevcut turun durumunu otomatik kapatmaz. Kilitli turda puan alanları ve kaydet/kesinleştir işlemleri salt okunurdur.

## Geçiş

`2026_09_05_180000_version_jury_evaluation_forms.php`, jüri atamalarına varsayılan sıfır olan `evaluation_version` sütunu ekler. Mevcut puanlar ve tamamlamalar korunur. Dağıtım öncesi açık eski formlar yenilenmelidir; yeni form alanı olmadan yazma isteği kabul edilmez. Şema ve uygulama birlikte dağıtılmalıdır. Geri alma, form sürümlerini sıfırlar; uygulama geri dönüşünde aktif istekler durdurulmalı ve formlar yenilenmelidir.

Gerçek uygulama veritabanına migration uygulanmadı. Test DB `tfsf_testing` ve tarayıcı DB `tfsf_evaluation_ui`, geçici MariaDB konteynerindedir; tarayıcı storage alanı uygulama storage alanından ayrıdır.

## Açık kapsam

- Yarışma düzeyindeki kilit doğruluk için geniş tutuldu. Fotoğraf işleme süresince aynı yarışmanın diğer yazmaları bekleyebilir. Hacim ve gecikme ölçümü, ardından aynı tutarlılık protokolünü koruyan daha dar kilit tasarımı gerekiyor.
- Jüri kurul oturumu/katılım/tutanak işlemleri, bütün yönetim ve sihirbaz yazma yolları henüz ortak protokol kapsamında değildir. Doğrudan SQL, toplu aktarım ve yeni servisler bu kilidi kendiliğinden kullanmaz.
- Finalist/ödül seçimi için sürümlü sonuç formu, kriter ve atama değişikliklerinin bütün yazma yollarında geçersizleştirme, değişmez eser revizyon arşivi ve yayın içeriğinin kalıcı snapshot kapsamı açık. Mevcut HMAC, içerik arşivi değildir.
- Bildirim outbox'ı, worker kesintisi sonrası onarım, gerçek aktarım ve pilot kabul açık. Transaction sonrası bildirim, kalıcı outbox garantisi sağlamaz.

## Doğrulama

15 yeni senaryo eklendi. Son tam SQLite: **720 geçti / 2.933 assertion**, MariaDB süreçlerine özel **23 atlama**. Son tam MariaDB 11.8: **743 geçti / 3.186 assertion**. Üç yeni bağımsız süreç testi ayrıca 39 assertion ile geçti. Pint 583 dosyada, Vite derlemesi ve izole migration rollback/yeniden uygulama başarılı. Gerçek tarayıcıda iki sekme, eski form reddi, puanların korunması ve kesinleştirme sonrası salt okunurluk doğrulandı.

Tam çıktılar [kanıt dosyasında](2026-09-05-evaluation-revision-locking-evidence.txt); 21 kaynak dosyanın test sonrası özeti [SHA-256 manifestinde](2026-09-05-evaluation-revision-locking-source-sha256.json). Testler mevcut davranışları da kapsar; ilk tam koşuda bulunan EYS `WaitingRequirements` geçişinin yanlış geri alınması düzeltilip iki motorun tamamı yeniden çalıştırıldı.
