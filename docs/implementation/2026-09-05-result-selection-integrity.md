# Finalist, ödül ve sonuç yayımlama bütünlüğü

On birinci yerel uygulama paketi. PK-09.03/09.05 kapsamındaki seçim ve yayımlama güvenliğini geliştirir; eski sistemin bütün ödül/sonuç işlevlerinin tamamlandığı anlamına gelmez.

## Davranış

Finalist seçimi, ödül ataması ve yayımlama formları güncel yarışma, tur, puan, eser, ödül ve oturum durumuna bağlı imzalı `result_context` taşır. İki sekmeden eski olanı yeni kaydı ezemez. Hata dönüşünde kullanıcı seçimi ve eski bağlam korunur; güncel durumu almak için sayfa yenilenir. Kontroller yarışma kilidi altındaki sunucu işleminde yapılır.

Hesaplanan sonuçların kaynakları veya kayıtlı sıraları değişmişse yeniden hesaplama gerekir. Puan/eser değişikliğinden sonra önceki ödül dağıtımı yeniden gözden geçirilip kaydedilmeden yayımlama yapılamaz. Ödül adları, maddi ödüller, miktarlar ve referans değişiklikleri de bu kontrolün içindedir. Ödül isteği bütün tanımlı kontenjanları açıkça içermelidir; eksik/yabancı/fazladan alan eski atamaları sessizce silmez. Açıkça boş seçilen alan atamayı kaldırır; zorunlu ödül boşken yayımlama reddedilir.

İlk final turu kurulduktan sonra yeni bir finalist isteği mevcut tura eser ekleyemez. Geçişte önceki tur ödüllerinin kaldırılacağı ekranda açıklanır ve kaldırılan atamaların kimlik/kontenjan eşlemesi denetim olayına yazılır. Bu kayıt değişmez tarihsel yayın arşivinin yerine geçmez.

Resmî puan toplama yalnız onaylı başvuruların aktif eserlerini ve aynı kategoriye ait jüri/kriter atamalarını kullanır. Tamamlanan görev sayımı güncel jüri atamalarıyla sınırlandırılır; bağlantısı kaldırılmış görevlerin tamamlanma kayıtları başka jürinin eksikliğini kapatamaz. Mevcut ağırlıklı puan formülü korunur.

## Teknik uygulama

`CompetitionResultState` kaynak girdiler ve hesaplanmış sonuçları birlikte SHA-256 ile özetler. `results_state_hash` bu ikisini bağlar; `awards_context_hash` ödül tanımları ve atamalarını da bağlar. Sıralı sorgular 500 kayıtlık parçalarla okunur. Form bağlamı `APP_KEY` ile HMAC imzalıdır; `results_edit_version` aynı değerle yeniden hesaplamada bile önceki formu geçersiz kılar.

EYS görüntüleme verisi ve bağlamı aynı yarışma kilidi altında okunur. Yazma, denetim olayı ve sürüm değişikliği ortak transaction içindedir. Önceki paketteki üye/jüri/kurum kilit protokolü kullanılır. Bildirimler commit sonrasındadır. Yetki middleware'i ve gerçek form bağlamları testlerde atlanmaz.

## Geçiş

`2026_09_05_200000_version_result_selection.php`, yarışmaya varsayılan sıfır `results_edit_version`; değerlendirme turuna nullable `results_state_hash` ve `awards_context_hash` ekler. Henüz yayımlanmamış eski sonuçlar yeniden hesaplanmalı, ödüller yeniden gözden geçirilmelidir. Daha önce yayımlanmış sonuçlar otomatik yeniden yazılmaz. Eski açık formlar yenilenmelidir; rollback/yeniden migrate sürüm ve özetleri sıfırlar.

Gerçek uygulama DB'sinde migration, veri aktarımı veya dağıtım yapılmadı. Migration geri dönüşü yalnız ayrı `tfsf_results_ui` şemasında; otomatik testler `tfsf_testing` üzerinde çalıştırıldı. Tarayıcı storage alanı ayrı, hesaplar sentetik, posta sürücüsü `array` idi.

## Doğrulama

16 özellik testi ve 3 MariaDB süreç testi eklendi. Süreç testleri iki ödül formu, ödül değişimi/yayımlama ve üye revizyonu/yayımlama yarışlarını kapsar. Hatalı önbellek sırası, yabancı kriter, eski form, eksik kontenjan, ödül metni değişimi ve denetim hatasında rollback ayrıca doğrulandı.

Nihai SQLite: **748 geçti / 3.157 assertion**, yalnız MariaDB süreçleri için **29 atlama**. Nihai MariaDB: **777 geçti / 3.490 assertion**. Pint **597 dosyada**, Vite derlemesi, migration geri dönüşü/yeniden uygulama ve `git diff --check` başarılı.

Gerçek Chrome ile iki sekme çakışması, yeniden hesaplama, ödülleri yeniden onaylama, başarılı yayımlama, yayımlanan atamaların salt okunurluğu ve finalistten planlı kurul oturumuna geçiş doğrulandı. Masaüstü ve mobil ekranlar incelendi. Ek final tarayıcı kontrolü başlangıçta aynı adresi kullanan iki ayrı formu karıştırdı; seçici düzeltildi ve uygulama değişmeden geçti.

[Doğrulama çıktıları](2026-09-05-result-selection-integrity-evidence.txt) · [15 kaynak dosyasının SHA-256 manifesti](2026-09-05-result-selection-integrity-source-sha256.json).

## Açık kapsam

Eski puanlama modları, çevrimdışı/harici sonuç aktarımı, değişmez fotoğraf/kimlik/ödül yayın arşivi, bütün yönetim ve referans yazmalarının ortak kilit protokolü, bildirim outbox'ı, importer ve pilot kabul açık. Parçalı hash okuması tüm ekranı sayfalı hale getirmez; EYS hâlâ sonuçları yükler. Yarışma düzeyindeki kilidin gecikmesi ve sorgu yükü hacim testinde ölçülmelidir.

Eşit sıra/puandaki eserlerin finalist ve ödül seçeneklerinde ayrılması için anonim sabit eser kodu ve güvenli önizleme gerekir. Yeni/henüz hesaplanmamış turdaki uyarıların başlangıç durumunu daha doğru anlatması ve eski denetim etiketlerinin çevrilmesi de arayüz takibindedir. Bu inceleme tam erişilebilirlik veya bütün eski işlevler için kabul değildir.
