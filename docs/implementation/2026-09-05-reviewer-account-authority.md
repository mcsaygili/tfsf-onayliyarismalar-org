# Onay kararlarında güncel hesap yetkisi

Yirminci yerel uygulama paketi, PK-01.01/01.03 ve PK-02.03 kapsamındaki hesap durumu ile karar yazımı arasındaki yarışı kapatır. Fotoğraf onayı, normal ön kayıt kararı ve özel izinli doğrudan ön kayıt aynı korumayı kullanır.

## Bulgu ve değişiklik

Temsilcinin fotoğraf onay policy dalında aktiflik koşulu yoktu. HTTP oturum denetiminin dışında doğrudan servis çağrısı pasif temsilciyle karar yazabiliyordu. Önce güvenli beklentiyi ifade eden test çalıştırıldı ve başarısız oldu. Ayrıca hesabı sıradan bir sorguyla yeniden okumak, onay transaction'ı tamamlanmadan yapılan hesap değişikliğine karşı kilit sağlamıyordu.

`ReviewerAccountLock`, yarışma mutex'i alındıktan sonra temsilci veya kurum personeli/sekreterya hesabını `FOR UPDATE` ile yeniden okur. Hesap var olmalı, aktif olmalı ve isteği başlatan aktörün `security_stamp` değeri güncel değerle eşleşmelidir. Hesap kilidi kararın transaction'ı bitene kadar tutulur. Başka aktör türleri ve transaction dışındaki kullanım reddedilir. Fotoğraf policy'si ayrıca aktiflik şartı uygular.

Hesap değişikliği önce kilidi aldıysa karar isteği bekler ve commit sonrası güncel hesapla denetlenir. Pasifleştirme, silme veya güvenlik damgasını değiştiren iletişim/kimlik güncellemesi eski isteği geçersizleştirir. Kapatılıp yeniden açılmış hesap aynı aktiflik değerine dönse de damga farklı olduğundan eski istek canlanmaz. Yeni oturum/güncel hesap bağlamıyla izinli işlem yapılabilir. Karar önce hesap kilidini aldıysa işlem o anda geçerli yetkiyle tamamlanır; daha sonraki pasifleştirme geçmiş kararı iptal etmez.

Yarışma ataması ve özel doğrudan onay izni mevcut yarışma mutex'i/policy sınırlarıyla yeniden denetlenir. Bu paket bütün yönetim işlemlerinin veya bağlı kurum durumunun eşzamanlılık denetimini tamamlanmış saymaz.

## Doğrulama

Yedi özellik testi: pasif temsilci, kapatılıp açılmış hesabın eski/yeni bağlamı, kurum görevlisinin değişmiş e-postası, silinen temsilci, eski ön kayıt onay yetkisi, ilgisiz üye aktörü ve pasif temsilci policy reddi.

Altı bağımsız MariaDB HTTP süreç senaryosu: fotoğraf/normal ön kayıt/doğrudan ön kayıt × pasifleştirme/kapatıp yeniden açma. Ana süreç hesap güncellemesini açık transaction içinde tutar. HTTP süreci eski commit edilmiş hesapla oturum açar; bağlantı kimliğiyle `PROCESSLIST` üzerinden temsilci hesabının kilitli okumasına ulaştığı görülmeden ana süreç commit etmez. Ardından HTTP 404, değişmemiş fotoğraf/ön kayıt durumu ve sürümü, fotoğraf karar olayının oluşmaması doğrulanır. Bu test yalnız izole `tfsf_testing` MariaDB'sinde çalışır.

İlk test düzeni `INNODB_LOCK_WAITS` üzerinden beklemeyi gözlemleyemedi; altı test gözlem assertion'ında başarısız oldu. Aynı bekleyen işçinin sorgusu `PROCESSLIST` içinde görüldüğünden test bağlantı kimliği ve gerçek `FOR UPDATE` sorgusunu izlemeye geçirildi. Veritabanı iç durumunun neden bu görünümde raporlanmadığına ilişkin bir iddia yoktur; başarılı HTTP sonucu varsayılmadan commit sonrası yanıt ve veri ayrıca doğrulanır.

Odaklı regresyon paketi 65 test / 369 assertion; odaklı MariaDB 13 test / 85 assertion geçti. Tam SQLite: **861 geçti / 3.952 assertion**, MariaDB'ye özel **48 atlama**. Tam MariaDB: **909 geçti / 4.489 assertion** (308,62 saniye). İki tam koşuda da gerçek qpdf/ClamAV araç testleri sentetik test imzalarıyla etkin; bu üretim antivirüs kurulumu kanıtı değildir. Pint **661 PHP dosyasında** ve diff kontrolü geçti.

[Test çıktıları](2026-09-05-reviewer-account-authority-evidence.txt) · [Kaynak içerik manifesti](2026-09-05-reviewer-account-authority-source-sha256.json).

## Dağıtım ve sınırlar

Yeni migration, izin veya hesap verisi yoktur. Mevcut güvenlik damgası altyapısı kullanılır. Eski proje ve gerçek veritabanı değiştirilmedi; veri aktarımı veya dağıtım yapılmadı. Sentetik test bildirimleri dış alıcıya gönderilmez. Ekran/rota/metin değişmediğinden yeni tarayıcı görsel turu yapılmadı.

Kurum/sekreterya aktif ve arşiv yarışma ayrıntıları, katılımcı/istatistik/raporlar, kalan rol izinleri, eski veri aktarımı ve pilot kabul sonraki işlerdir. Genel özellik eşdeğerliği hedefi devam eder.
