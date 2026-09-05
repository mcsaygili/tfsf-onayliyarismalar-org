# Kurul oturumu, beyan ve karar bütünlüğü

Onuncu yerel uygulama paketi. PK-08.05/08.06 ve PK-09.04 kapsamındaki çevrimiçi kurul güvenliğinin bir bölümüdür. Çevrimdışı sonuç aktarımı, bütün eski tutanak alanları, imza ve tarihsel sürümleme henüz tamamlanmadı.

## Yapılan değişiklik

Oturum planı, katılım listesi, açma/kapama, tutanak ve denetim olayı tek transaction içinde yazılır. Kapatma önkoşulu veya denetim kaydı başarısız olduğunda tutanak ve katılım da geri alınır. Jüri çıkar çatışması beyanı aynı yarışma kilidini kullanır; beyan, oturum planı ve kurul kararı ortak `session_version` ile gönderilir. Eski sekme sonraki kaydı ezemez; bir beyan veya karar değiştikten sonra eski bilgilerle oturum kapatılamaz.

Yeter sayı artık yalnız **katılan, aktif hesabı ve yarışmada güncel kategori ataması bulunan, açıkça çıkar çatışması olmadığını beyan etmiş** jüri üyelerini sayar. Varsayılan `false`, verilmiş beyan sayılmaz. Çatışma beyanında açıklama hem HTML'nin `1` değeri hem JSON/PHP boolean `true` için zorunludur. Çatışma yok beyanı kaydedildiğinde artık geçerli olmayan açıklama temizlenir. Başka jüri veya başka oturum adına beyan yazılamaz.

Kapalı oturum ve yayımlanmış sonuç, plan/katılım/tutanak/beyan düzenlemesine kapalıdır. Oturum sağlama işlemi, açık veya kapalı oturuma kendiliğinden yeni katılım kaydı eklemez. Planlanan oturuma yeni atama eklendiğinde form sürümü değişir. Kapalı oturumu değiştirmek için ileride gerekçe ve geçmiş kaydı olan ayrı düzeltme akışı gerekir.

Kurul kararlarının kaydı ortak oturum sürümünü artırır. Yabancı karar kimlikleri sessizce atlanmaz. Kısmi istekte değiştirilmeyen kararlar da hesaba katılır; aynı kategoride aynı sırayı iki eser alamaz. Finalistlikten çıkış ve sıra zorunluluğu mevcut kurallarla sürer. Bu paket ödül atama formlarının veya ilk finalist seçim ekranının sürümlemesini tamamlamaz.

## Arayüz

EYS ve jüri formlarında güncel oturum sürümü gönderilir. Hata dönüşünde eski sürüm ve kullanıcı girdisi korunur; sayfa yenilenmeden eski form tekrar güncel kabul edilmez. Hata mesajı görünür ve `role=alert` ile işaretlidir. Kapalı oturumda alanlar salt okunurdur, kaydet düğmeleri gizlenir ve katılım durumu metin olarak okunur. Sonuç/kurul alanları mevcut arayüz kimliğini korur; yeni hata ve durum mesajları TR/EN tanımlıdır. Eski ekranın tüm sabit Türkçe metinlerinin çevirisi bu paketin kapsamı değildir.

## Geçiş ve sınırlar

`2026_09_05_190000_version_jury_sessions.php`, `competition_jury_sessions.version` sütununu varsayılan sıfırla ekler. Mevcut oturum, beyan, karar ve tutanaklar korunur. Mevcut açık oturumlarda daha önce açık beyan vermemiş üyeler, yeni karar/kapatma işleminden önce beyan vermelidir. Mevcut kapalı tutanaklar geriye dönük değiştirilmez. Eski açık formlar dağıtımda yenilenmelidir; rollback/re-migrate sürümü sıfırlar, aktif formlarla birlikte uygulanmamalıdır.

Gerçek uygulama DB'sine migration uygulanmadı. Testler yalnız geçici `tfsf_testing` ve `tfsf_session_ui` şemalarında çalışır. Arayüz storage alanı ayrıdır.

Mevcut EYS yeter sayı ayarı (1–30) korunur; sabit kurum/kategori alt sınırı ayrı iş kararıdır.

Açık işler: sonuç/ödül seçim ekranlarının güncel içerik sürümü, kapalı tutanak düzeltme ve imza akışı, kapanıştaki kimlik/katılım/kararların değişmez arşivi, kurul başladıktan sonraki atama/hesap değişikliklerinin bütün yazma yollarında ortak protokole bağlanması, bildirim outbox'ı, aktarım ve pilot kabul. Yarışma düzeyindeki kilidin yoğun yükte bekleme etkisi ayrıca ölçülmelidir.

## Doğrulama

12 özellik testi ve 3 MariaDB süreç testi eklendi. Süreç testleri iki EYS plan formu, çatışma beyanı/kapatma ve karar/kapatma yarışlarını kapsar. Başlangıçta geçerli yeter sayı da doğrulanır; yetki middleware'i atlanmaz. Mevcut uçtan uca yarışma senaryolarına gerçek jüri beyan adımları eklendi.

Son tam SQLite: **732 geçti / 3.027 assertion**, MariaDB süreçlerine özel **26 atlama**. Son tam MariaDB 11.8: **758 geçti / 3.318 assertion**. Son arayüz regresyon grubu: 21 test / 305 assertion. Pint 589 dosyada, Vite derlemesi ve izole migration rollback/yeniden uygulama başarılı. Gerçek tarayıcıda EYS/jüri iş akışı ve masaüstü/mobil salt okunurluk doğrulandı.

[Doğrulama çıktıları](2026-09-05-jury-session-integrity-evidence.txt) ve [14 kaynak dosyanın SHA-256 manifesti](2026-09-05-jury-session-integrity-source-sha256.json). Son tam koşular, mobil düzenlemede bulunan Blade derleme hatası giderilip gerçek GET render kontrolleri eklendikten sonra alındı.
