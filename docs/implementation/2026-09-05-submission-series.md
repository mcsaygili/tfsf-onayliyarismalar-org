# Kategori başvurusunun fotoğraf serisi olarak gruplanması

On yedinci yerel uygulama paketi, PK-06.04'ün kategori/katılımcı serisi alt kapsamını uygular. Bir katılımcının aynı kategoriye gönderdiği fotoğraflar; kalıcı anonim seri kimliği, fotoğraf sırası ve ortak hikâyeyle birlikte gösterilir. Kişisel portföy ayrı kalır.

## Eski sistemdeki davranış ve uygulanan karar

Eski `ThirdParty/Frontend/Models/Yarisma/YarismaKayitModel.php`, `yarisma_katilim` üst kaydında kategori hikâyesini ve `yarisma_katilim_detay` satırlarında fotoğraf/sıra/hikâye bilgilerini tutar. Kategori alanı `uye_fotograf_grup`, fotoğrafların katılımcı dizini altında düzenlenmesini seçer. Eski üye katılım/arşiv ekranları bu gruplu dosya yolunu kullanır. `ThirdParty/Juri/Models/JuriYarismaModel.php` puanları `arsiv_yarisma_katilim_detay_uuid` üzerinden, yani fotoğraf düzeyinde bağlar. Temsilci ödül ekranı da fotoğraf kayıtlarını seçer.

Bu pakette **bir kategori başvurusu = bir seri** kararı uygulandı. Puan ve ödül hedefi fotoğraftır. Kullanıcıya tek seri puanı tercihi ayrıca soruldu; yanıt gelmediği için eski kayıtlarda doğrulanan fotoğraf puanlaması korundu. Bir kategoride birden fazla bağımsız seri, seriye tek puan veya seri ödülü uygulanmış sayılmaz; bu yeni iş kararları ayrı değerlendirme/ödül modeli gerektirir. Önceki incelemede seri kavramı için daha geniş öneriler bulunması, bunların eski kodda aynı biçimde mevcut olduğunun kanıtı olarak alınmadı.

## Kategori, kimlik ve sıra

Kurum sihirbazının 6. adımına `photos_grouped` ayarı eklendi. Varsayılan kapalıdır. Eski form alanı göndermediğinde mevcut değer korunur; açıkça yanlış boolean verilirse istek reddedilir. İlk kategori başvurusu oluştuğunda gruplama modu kilitlenir. Sihirbaz yazması yarışma kilidi altında transaction kullanır; hata diğer kategori değişikliklerini de geri alır. Şartname bağlamı boolean değerini ve istenen dilde seri özetini taşır.

Yeni tablo gerekmeden mevcut `CompetitionSubmission`, serinin üst kaydıdır. `series_code`, kriptografik rastgele 16 hex karakter ve DB tekilliğiyle üretilir; ekranda `S-XXXX-XXXX-XXXX-XXXX` biçimindedir. Bütün kategori başvurularında bulunur; bireysel kategorilerde seri olarak gösterilmez. Üye kimliği, adı veya e-postasından türetilmez. Normal model yazmasında değiştirilemez; kopyalanan yeni başvuru yeni kod alır. Fotoğrafların kendi anonim eser kodları korunur.

Seri etkinse bağımsız “fotoğraf sırası” bayrağı kapalı olsa da sıra alanı zorunludur. Üye sırayı 1..N arasında boşluksuz ve tekrarsız gönderir. Fotoğraf kimlikleri mevcut aktif kümenin tamamıyla eşleşmelidir; başka üyenin veya başka başvurunun fotoğrafı eklenemez. Ortak hikâye mevcut `category_story` alanındadır; ilgili şartname bayrağı açılmışsa zorunlu, aksi halde seride isteğe bağlıdır. Ayrı, gereksiz bir hikâye kopyası oluşturulmadı.

Sıra/hikâye değişikliği mevcut başvuru sürümü ve yarışma kilidiyle korunur. Değerlendirme başlamışsa değişiklik önceki puan kesinleştirmesini yeniden açar. Geri çekilen fotoğraf aktif gruptan çıkar, kalan seri kimliği değişmez. Bir fotoğraflı grup da kabul edilir; eski kategori fotoğraf üst sınırı uygulanır. Yeni bir asgari seri uzunluğu varsayılmadı.

## Jüri, seçim ve sonuç

Jüri yalnız onaylı başvuruların aktif fotoğraflarını görür. Seriler anonim kodlarına göre, içlerindeki fotoğraflar kayıtlı sıraya göre gösterilir. Ortak hikâye seri başlığında bir kez yer alır. Fotoğraf kodu ve serideki konum görünür; katılımcı kimliği ve orijinal dosya adı gösterilmez. Hikâye HTML olarak çalıştırılmaz. Metne üyenin kendi kimliğini yazmasını teknik bir anonimleştirme iddiasıyla örtmüyoruz; mevcut kimlik belirtmeme yönlendirmesi korunur.

Etiketler fotoğrafa ve jüri üyesine özel kalır. Seri kategorisinde eşleşen tek fotoğraf, bütün seriyi görünür yapar; etiket o serinin diğer fotoğraflarına otomatik uygulanmaz. Arayüz bunu açıklar. Bireysel kategoride eski fotoğraf filtresi devam eder. Her fotoğrafın kriter puanı ayrı kaydedilir/kesinleştirilir.

Gruplama modu ve seri kodu jüri formunun HMAC bağlamına, gruplama/kod/sıra/hikâye de sonuç güncellik hesabına katılır. Eski bağlamla puan kaydı reddedilir. EYS eser önizlemeleri ve ödül seçenekleri seri kodunu gösterir; finalist ve ödül işlemleri fotoğraf düzeyindedir. Tam seriyi tek işlemle finalist/ödül yapma bu paketin kapsamı değildir.

Yayın snapshot'ında sonuç fotoğrafının seri kodu/sırası, üyenin bütün onaylı fotoğraflarında seri kimliği/kodu, sıra ve ortak hikâye saklanır. Üye arşivinde başvuru silinse bile bu bilgiler okunur. Kamu ödüllü fotoğrafta seri kodunu görür; ortak hikâye ve ödülsüz görselin özel erişim sınırı korunur. Mevcut snapshot sürüm 3'e isteğe bağlı alanlar eklendi; eski snapshot'ta alan yoksa canlı modelden geçmiş seri uydurulmaz.

## Portföy ayrımı

Portföyden seçilen fotoğraf mevcut kopyalama servisiyle yarışmanın özel alanına alınır. Kaynak fotoğraf ve kaynak dosya silinse de yarışma kopyası, güvenli jüri türevi ve seri kimliği korunur. Bu pakette portföy albümü, çoklu seri kataloğu veya dosyaları sahip kimliğine göre public dizine taşıma eklenmedi.

## Geçiş ve geri dönüş

Migration: `2026_09_05_260000_group_submission_photos_as_series.php`. Kategori bayrağı varsayılan false; mevcut başvurulara 500'lü parçalar halinde rastgele seri kodu atanır, sonra alan zorunlu hale gelir. Bakım penceresinde uygulanmalı; eski açık jüri/sonuç formları yenilenmeli, yayımlanmamış hesaplar yeniden hesaplanıp ödül seçimi kontrol edilmelidir. Yayınlanmış snapshot yerinde değiştirilmez.

Eski `uye_fotograf_grup` değerleri yeni bayrağa, eski katılım kimlikleri yeni başvuru kimliklerine kontrollü aktarılmalıdır. Dosya grupları klasör adına bakılarak anonim kimlik gibi kullanılmamalı; üye/yarışma/kategori sahipliği ve dosya manifestosu doğrulanmalıdır. Gerçek veri aktarımı uygulanmadı.

**Rollback seri bayrağını ve canlı seri kodlarını kaldırır.** Başvuru/fotoğraf/hikâye/sıra kayıtları ve yayın snapshot'ları korunur. Yeniden uygulama kodları yeniden üretir ve kategori bayrağını false yapar; aynı kimlik ve ayarla geri dönüş için DB yedeği gerekir. Gerçek uygulama veritabanında migration, rollback veya dağıtım yapılmadı.

## Doğrulama ve kalan işler

12 seri özelliği testi, mevcut kategori testlerine 1 ayar senaryosu ve 1 MariaDB veri koruma/migration testi eklendi. Sıra doğrulaması, sahiplik, başka üyelerin ayrılması, taslak serinin jüriye görünmemesi, hikâye kaçışı, eski jüri bağlamı, mod kilidi, kalıcı kod, fotoğraf geri çekme, portföyden bağımsızlık ve arşiv/kamu ayrımı doğrulanır. Yeni seriye tek puan veya tek ödül için kabul testi yazılmış gibi sayılmaz.

**Tam SQLite: 824 geçti / 3.757 assertion, MariaDB’ye özel 36 atlama. Tam MariaDB: 860 geçti / 4.159 assertion.** Pint 639 PHP dosyasında, Vite derlemesi ve `git diff --check` geçti. İki tam koşuda da önceki PDF tarayıcısı entegrasyonları etkinleştirildi. Odaklı SQLite grubu 27 test / 118 assertion; odaklı MariaDB seri/migration grubu 13 test / 73 assertion geçti.

Chrome uçtan uca akışında kategori ayarı, sıra/hikâye, bütün seri etiketi, ayrı fotoğraf puanı/kesinleştirme, EYS ödül/yayın ve üye/kamu arşivi doğrulandı. TR/EN, 1440/390 px ve altı ekran görüntüsü; iki toplu görsel inceleme. Son koşuda konsol/sayfa hatası yok. İzole migration provasında iki başvurunun diğer alanları, fotoğraflar ve yayın snapshot'ları korundu; yeni seri kodları üretildi ve gruplama sıfırlandı.

[Komutlar ve sonuçlar](2026-09-05-submission-series-evidence.txt) · [Kaynak manifesti](2026-09-05-submission-series-source-sha256.json).

PK-06.04'ün eski veri aktarımı ve pilot kabulü, PK-09'un seri/kişi ödül hedefleri, toplu seri işlemleri, çevrimdışı kurul serisi ve genel rapor/ZIP aktarımı açık. Ön kayıt istisnaları, dernek hesapları ve diğer iş paketleri de genel hedef kapsamında sürer.
