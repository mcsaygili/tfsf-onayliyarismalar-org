# Anonim eser kodu ve güvenli seçim önizlemeleri

On ikinci yerel uygulama paketi. PK-08.03 ve PK-09.03/09.05 kapsamında jüri, finalist, kurul ve ödül seçiminde eserin ayırt edilmesini tamamlar. Eski sistemin tüm sonuç, arşiv ve toplu ödül işlevleri için kabul değildir.

## Kullanım senaryosu

Önceki finalist ve ödül seçenekleri yalnız kategori, sıra ve puan gösteriyordu. Eşit sıra/puandaki iki eser aynı görünüyordu. Her yarışma fotoğrafına kalıcı anonim eser kodu eklendi. Aynı kod jüri değerlendirmesinde, finalist kartında, kurul karar satırında, sonuç tablosunda ve ödül seçeneğinde kullanılır. Seçim değişince ödül alanının altında ilgili eserin güvenli önizlemesi gösterilir.

Önizleme bağlantısı yeni sekmede büyük görüntüyü açar. Checkbox etiketi bağlantıdan ayrıdır; önizleme açmak finalist seçimini değiştirmez. Klavye ile seçim çalışır. Görsel eksik veya temizlenmemişse, yahut HTTP isteği başarısızsa açıklama gösterilir; orijinal dosyaya geri dönüş yapılmaz. Boş ödül seçimi ayrıca açıklanır. JavaScript kapalı olduğunda kodlu yerel seçim alanı ve sonuç tablosundaki güvenli önizleme bağlantıları kullanılabilir; seçimle değişen önizleme Alpine gerektirir.

Yeni turun henüz hesaplanmamış olması ile daha önce hesaplanmış puanların değişmesi artık farklı mesajlarla açıklanır. Henüz kaydedilmemiş ödül dağıtımı için ilk atama mesajı gösterilir. Bu metinler TR/EN tanımlıdır.

## Güvenlik ve veri modeli

`competition_submission_photos.anonymous_code`, 16 büyük harfli hexadecimal karakterdir ve veritabanında unique/not-null koşulludur. Kod dört karakterlik gruplarla gösterilir. Üye, orijinal dosya adı, EXIF veya fotoğraf adı türetilmez; rastgele üretilir. Yetkilendirme belirteci değildir. Mevcut kodlarla çakışma üretim öncesi sorgulanır; eşzamanlı çakışmada unique kısıtı kaydın yanlış esere bağlanmasını engeller ve işlem başarısız olur.

Eloquent yeni fotoğraf oluştururken kod üretir; `replicate()` ile kopyalanan yeni fotoğraf farklı kod alır. Model üzerinden kod güncellemesi reddedilir. Yeniden puan hesaplama ve geri çekme/geri alma aynı fotoğrafın kodunu korur. Bu koruma doğrudan SQL yazmalarını değişmez kılan bir DB trigger değildir; importer ve yönetim yazmaları bu sözleşmeyi korumalıdır.

EYS önizlemesi mevcut permission/policy ve yarışma–fotoğraf eşleşmesini korur; ayrıca onaylı başvuru ve geri çekilmemiş eser gerektirir. Yalnız `jury_sanitized_at` işaretli özel disk kopyası sunulur. Anonim kod kaynak durum özetine katılır; doğrudan değiştirilmiş kodla eski seçim formu geçerli olmaz.

## Eski sistem dayanağı

Eski `ThirdParty/Temsilci/Views/Yarisma/ArsivYarismaIslemleri/yarismaOdulIslemleriTekilAtamaView.php` satır 58–68, ödül alan fotoğraf seçimini; 162–173, küçük fotoğraf ve büyük görüntü bağlantısını; 217–240, en az üç karakterle sunucuda fotoğraf aramasını içerir. `ThirdParty/Juri/Views/Yarisma/oylamaView.php` satır 147–159, jüri fotoğrafını büyük görüntü/zoom kaynağıyla gösterir.

Yeni pakette seçim sırasında görseli tanıma ve büyük görüntü açma karşılandı. Eski arşiv fotoğraf araması, toplu sergileme/kabul, bağımsız seri/grup ödülleri ve bütün zoom araçları bu pakette tamamlanmadı. Eski dosya yollarındaki üye kimliğini yeni seçim ekranına taşımak gerekmez.

## Geçiş ve işletim

`2026_09_05_210000_identify_submission_photos_anonymously.php` mevcut fotoğrafları 500 kayıtlık parçalarla kodlar ve sütunu zorunlu yapar. Mevcut fotoğraf içeriği, yol, kimlik, sıralama ve metadata korunur. Geçiş sırasında yazmalar durdurulmalı; bakım penceresi ve yedek gerekir. Başarısız yarım DDL geçişi için operatörün şema/veriyi inceleyerek yedekten geri dönmesi veya geçişi tamamlaması gerekir; migration kaldığı yerden otomatik devam eden bir aktarım işi değildir.

Kodlar artık kalıcı veridir: yedek ve importer bunları korumalıdır. ORM üzerinden yeniden oluşturmak yeni kod üretir. Rollback sütunu kaldırır; yeniden migrate farklı kod üretir. Kodlar kullanıcıya dağıtıldıktan sonra geri dönüş, kodları da içeren yedeği geri yüklemeyi gerektirir. Kaynak özetinin kapsamı genişlediği için henüz yayımlanmamış eski sonuçlar yeniden hesaplanıp ödüller gözden geçirilmelidir. Yayımlanmış sonuçlar otomatik yeniden yazılmaz.

Gerçek uygulama veritabanında migration veya dağıtım yapılmadı. Otomatik test şeması `tfsf_testing`; tarayıcı şeması `tfsf_results_ui`, storage alanı ayrı ve hesaplar sentetiktir. Posta sürücüsü `array` kullanıldı.

## Doğrulama

Altı özellik testi: eşit puan/sıradaki seçeneklerin ayrılması, kodun yeniden hesaplama ve geri çekme/geri alma sırasında korunması, model güncellemesinin reddi, jüri/kurulda aynı kod, yetki/kapsam ve güvenli kopya, eksik/çekilmiş/reddedilmiş eser ile ilk durum mesajları. Ek MariaDB migration testi, mevcut kayıtların yalnız kod alanı eklenerek korunmasını doğrular.

Nihai SQLite: **754 test / 3.203 assertion**, MariaDB süreç/migration testleri için **30 atlama**. Nihai MariaDB: **784 test / 3.543 assertion**. Pint **600 PHP dosyasında**, Vite derlemesi ve `git diff --check` başarılı. İzole UI şemasında son migration rollback/yeniden uygulama geçti. Yedi yeni test MariaDB'de 53 assertion ile geçti.

Chrome/Playwright ile fotoğraf ve kod eşleşmesi, dinamik ödül önizlemesi, yeni sekmede görüntü açma, HTTP 404 halinde açıklama, boş seçim, kayıt sonrası seçimin korunması, final kuruluna geçiş, klavye seçimi ve mobil jüri/ödül alanları doğrulandı. Tarayıcı testlerinde JavaScript hatası görülmedi. Browser plugin mevcut olmadığından Playwright kullanıldı.

[Doğrulama çıktıları](2026-09-05-anonymous-work-previews-evidence.txt) · [12 kaynak dosyasının SHA-256 manifesti](2026-09-05-anonymous-work-previews-source-sha256.json).

Tarayıcı kontrolünde ilk test, puana göre sıralanmış listedeki sıra ile seed fotoğraf sırasını karıştırdı; gerçek checkbox kimliğinden kod okunarak test düzeltildi. Uygulama kodunda bu sebeple değişiklik gerekmedi. İlk özellik kontrolünde enum/string karşılaştırması bulundu ve düzeltildi. Migration testinin tüm eski migration'ları geri alan SQLite teardown'ı mevcut bir named-foreign-key kısıtına takıldı; veri koruma/rollback testi MariaDB üzerinde çalışır. SQLite yeni migration'ın ileri yönünü normal test kurulumunda uygular. Bütün eski migration'ların SQLite geri dönüş uyumluluğu açık iştir.

## Kalan işler

Büyük yarışmalarda aramalı/sayfalı eser seçimi ve EYS sayfasının tüm sonuçları yüklemesinin iyileştirilmesi; imzalı/değişmez yayın içeriği ve kimlik arşivi; arşiv/harici sonuç aktarımı; seri ve ek belgeli katılım; geniş kapsamlı erişilebilirlik ve pilot kabul. Jüri ekranının diğer erişilebilirlik eksikleri ve eski sabit Türkçe alanlar bu paketin kabul kapsamı değildir.
