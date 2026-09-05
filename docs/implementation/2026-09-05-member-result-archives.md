# Üye sonuç ve puan kartı arşivi

On dördüncü yerel uygulama paketi. PK-10.01/10.02 ve PK-10.03 içindeki üye sonuç görünümü ve puan kartını yayın anındaki kayda bağlar. Genel istatistikler, bütün raporlar, eski veri aktarımı ve PK-10'un tamamı bitmiş değildir.

## Davranış

Yeni sonuç yayınları snapshot sürüm **3** kullanır. Her onaylı başvurunun aktif eserleri için sahip/başvuru kimliği, anonim eser kodu, kategori adı, fotoğraf sırası, beyan edilen başlık/yer/tarih/hikâye, kategori hikâyesi, çekim cihazı adı ve tur bazında anonim puan kartları saklanır. Final listesine giremeyen eserlerin önceki tur puanları da korunur. Taslak, reddedilmiş veya geri çekilmiş eserler bu kayda dahil edilmez.

Her anonim değerlendirme puanı mevcut ağırlıklı hesaplamayı kullanır. Tur ortalaması yuvarlanmış jüri ortalamalarının basit ortalaması değildir: toplam ağırlıklı puan / toplam değerlendirilmiş ağırlıktır. Eksik kriter, kesirli ağırlık ve yuvarlama senaryolarında resmi sonuçla eşitlik test edildi. Sorgu aynı yarışma/tur/kategori ve atanmış jüri kapsamını denetler; gönderilmemiş puanları dışarıda bırakır. Snapshot'taki puan satırları jüri kimliği veya adı taşımaz; numaralı etiketler görüntüleme dilinde üretilir.

Üye yarışma sayfasındaki sonuç listesi artık ilan edilmiş kategori/ödül/sıra/ortalama ve eser kodunu okur. Yeni `competitions.results.mine` sayfası üyenin kendi arşivini gösterir. Başvuru ekranında aynı arşiv üstte, güncel başvuru ayrıntıları açılır bölümde gösterilir. İlan edilmiş sonuç, tur puan kartlarından ayrı etiketlenir; final listesinde olmayan eser açıkça belirtilir. Başvuru kaydı sonradan fiziksel silinse de yarışma ve üye kimliği üzerinden kişisel arşiv okunabilir.

Yayın geçmişi bir kez oluştuğunda puan kartı canlı tablolardan yeniden üretilmez. Geri çekilmiş, zamanı gelmemiş, eski sürümde kalmış veya yarışması askıya alınmış yayın puan/görsel açmaz. Eski v1/v2 snapshot'ta ayrıntılı kart yoksa açıklama gösterilir. Yayın geçmişi henüz olmayan yarışmadaki mevcut canlı geri bildirim davranışı korunur.

## Özel görseller ve erişim

Sürüm 3 yayınında bütün onaylı/aktif eserlerin temizlenmiş jüri kopyası arşivlenir; yalnız ödüllü görseller kamuya açıktır. Yeni `owner_user_id` ve `is_public` alanları dosya kaydında saklanır. Üye görsel adresi oturum/doğrulama, sahip, yarışma, yayın ve güncel yayın görünürlüğünü birlikte denetler. Ödülsüz görsel başka üyenin hesabından veya iki kamu görsel adresinden de alınamaz. Mevcut yetkili EYS arşiv erişimi korunur.

Üye genel sonuç listesi diğer üyelerin özel fotoğraflarını veya adlarını göstermez. Kişisel kartta yalnız snapshot'taki sahiplik eşleşmesi kullanılır. Kaynak fotoğrafın silinmesi veya güncel beyan/puan/kriter ağırlığının değişmesi eski kartı ya da arşiv görselini değiştirmez. Arşiv dosyaları mevcut SHA-256 doğrulaması ve `private, no-store` yanıtıyla sunulur.

Ödül almayan bir eserin güvenli görseli eksik olsa bile yayın bütünüyle başarısız olur; kısmi snapshot/dosya kaydı bırakılmaz. Normal exception ve dış transaction geri alma temizliği önceki paketten devam eder. `tfsf:audit-result-archives --verify-files` artık snapshot'ta bulunan kişisel eserlerin dosyalarını da denetler. Komut v2 kayıtlarında hiç kaydedilmemiş puan kartlarını üretemez; v2'yi yalnız ayrıntılı kart yok diye ayrıca hata saymaz.

## Migration ve geri dönüş

`2026_09_05_230000_scope_private_result_assets.php` özel sahiplik ve kamu görünürlüğü sütunlarını ekler. Önceki v2 dosyaları zaten yalnız ödüllü eserlerden oluştuğundan eski kayıtlar `is_public=true`, sahibi bilinmiyorsa `owner_user_id=null` olarak kalır. Güncel bilgiler tarihsel sahiplik/puanmış gibi doldurulmaz.

**Geri dönüş veri etkisi:** eski v2 kodu tabloda eşleşen bütün dosyaları kamuya açar. Bu nedenle `down()` önce özel görsel eşleme satırlarını siler, sonra yeni sütunları kaldırır. Dosyalar özel diskte ve snapshot içeriği DB'de kalır. Tekrar migrate, silinen eşleme/sahiplik satırlarını geri getirmez. Eksiksiz geri dönüş için DB ile özel arşiv dizini birlikte yedeklenmeli; eski uygulamayı çalıştırmadan önce bu migration geri alma veya eşdeğer mahremiyet önlemi uygulanmalıdır. Eski kodu yeni özel kayıtlarla doğrudan başlatmak uygun değildir.

MariaDB veri içeren geri dönüş testinde yalnız özel eşlemelerin kaldırıldığı, kamu eşlemelerinin ve dosyaların kaldığı, yeniden uygulamada kamu varsayılanının doğru olduğu ve snapshot JSON'unun değişmediği doğrulandı. Gerçek uygulama DB'sinde migration, veri aktarımı veya dağıtım yapılmadı.

## Doğrulama

10 yeni özellik testi ve bir MariaDB migration testi eklendi. Beş ağırlıklı puan senaryosu arşiv eşitliğiyle genişletildi. Önceki yayın arşivi testleri, artık iki farklı erişim sınıfındaki dosya bulunduğu için ilgili ödüllü eseri kimliğiyle seçer.

10 yeni özellik testi ve bir MariaDB migration testi. Tam SQLite: **776 geçti / 3.487 assertion**, **31 MariaDB atlaması**. Tam MariaDB: **807 geçti / 3.839 assertion**. Pint **611 PHP dosyasında**, Vite derlemesi ve `git diff --check` başarılı. Odaklı özellik grubu 27 test / 299 assertion; bağımsız migration grubu 1 test / 12 assertion geçti.

Chrome/Playwright ile gerçek EYS ödül/yayın formu, üye girişi, iki eserin arşivi, masaüstü/mobil görünüm, başvuru ayrıntısını açma-kapama, TR/EN, canlı veri değişiminden sonra aynı kart metni ve aynı görsel SHA-256, ödülsüz görsele kamu erişiminin reddi ve yayın geri alma doğrulandı. Konsol ve sayfa çalışma zamanı hatası yok. Browser plugin bulunmadığı için mevcut Playwright kullanıldı. İki görsel kontrol turunda tek başlık aralığı düzeltmesi yapıldı.

[Kanıt çıktıları](2026-09-05-member-result-archives-evidence.txt) · [Kaynak manifesti](2026-09-05-member-result-archives-source-sha256.json).

## Açık kapsam

Eski v1/v2 kartlarının güvenilir kaynakla aktarımı, seri/grup ve belgeli katılım, genel/demografik istatistikler ve tüm raporlar, kişisel veri saklama/maskeleme, tarihsel kurul tutanağı/ayrıntılı kriter dökümü açık. Mevcut kart toplamları korunur; bütün ham jüri kriter kayıtlarının değişmez arşivi bu pakette yapılmadı.

Snapshot ve ilişkili görseller tek yarışma kilidi altında toplanır. Büyük yarışmalarda JSON/bellek kullanımı, dosya kopyalama süresi ve kilit beklemesi yük testi gerektirir. Ani süreç kesintisinde referanssız dosya kalabilir; kalıcı dosya günlüğü/uzlaştırma henüz yok. Üretim veri hacmi, pilot kullanıcı kabulü ve bütün sistem erişilebilirlik denetimi yapılmadı. Genel hedef aktiftir.
