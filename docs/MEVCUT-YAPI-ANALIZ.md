# TFSF Onaylı Yarışmalar — Mevcut Yapı Analizi

> Bu doküman, `tfsf-onayliyarismalar` (eski/legacy) projesinin kod tabanı taranarak çıkarılmıştır. Amacı, sistemin **Laravel + PHP 8.5 + Tailwind** ile sıfırdan yeniden yazılması öncesinde mevcut mimariyi, veri modelini, iş kurallarını ve entegrasyonları tek bir referans kaynakta toplamaktır. Analiz edilen kaynak: `/Users/mcsaygili/workspace-tfsf/tfsf-onayliyarismalar`.
>
> Doküman tarihi: 2026-08-08

---

## 1. Genel Bakış

**TFSF Onaylı Yarışmalar**, Türkiye Fotoğraf Sanatı Federasyonu (TFSF) tarafından onaylanan fotoğraf yarışmalarının yönetildiği bir platform. Sistem; yarışma oluşturma/onaylama, katılımcı (fotoğrafçı) kayıt ve başvuru, jüri değerlendirme/oylama, ödül/sonuç ilanı ve arşivleme süreçlerinin uçtan uca yönetildiği çok taraflı (multi-tenant) bir uygulama.

**Teknoloji yığını:**

| Katman | Teknoloji |
|---|---|
| Backend framework | CodeIgniter 4 (`codeigniter4/codeigniter4`) |
| PHP sürümü | `>= 7.3` (composer.json) |
| Veritabanı | MySQL / MySQLi tek bağlantı grubu |
| Frontend CSS | Bootstrap 4 + **DashLite v2.5.0** (Softnio, ThemeForest admin teması) |
| Frontend JS | jQuery 3.6.0 + çok sayıda vendor plugin (TinyMCE, Splide, Plupload, jQuery Fancybox, Input Mask, jsTree, intl-tel-input, Noty, jQuery Confirm, Countdown) |
| Build tooling | **Yok** — hiçbir `package.json`/`gulpfile`/`webpack`/`vite` yok; tüm CSS/JS elle bakımı yapılan, önceden minify edilmiş statik dosyalar |
| E-posta | MailerSend (API), + Amazon SES / Brevo SMTP (PHPMailer tabanlı, kullanım durumu net değil) |
| SMS | NetGSM (Türkiye SMS sağlayıcısı) |
| Excel export | PhpSpreadsheet |
| Dosya yöneticisi | Responsive FileManager v9.14.0 (üçüncü parti, TinyMCE plugin'i olarak da bağlı) |
| Versiyon | `TFSF_VERSION = 3.9.3` (`configVersion.php`) |

Sistemde **ödeme, e-Devlet veya e-imza entegrasyonu bulunmuyor** — ödüller parasal değil, tanınma bazlı (sergileme / kabul / satın alma).

---

## 2. Mimari

### 2.1 `app/` vs `ThirdParty/` ayrımı — kritik bulgu

`app/` dizini görünüşte standart bir CI4 uygulaması gibi dursa da aslında **sadece ince bir bootstrap katmanı**: temel controller sınıfları, `Config/Autoload.php`, ortak `Libraries/Daisy/*`, `Helpers/*`. **Gerçek iş mantığı ve tüm view'lar `/ThirdParty/<Modül>/` altında**, CI4'ün modül otomatik-keşif mekanizmasıyla (`app/Config/Autoload.php` içindeki `$psr4` eşlemeleri) yükleniyor. Her modül kendi `Controllers/`, `Models/`, `Views/`, `Config/Routes.php`, `Libraries/`, `Language/` alt yapısına sahip, bağımsız bir mini-uygulama gibi çalışıyor.

Bu, tek bir kod tabanının **subdomain bazlı 6 ayrı "site"** sunduğu anlamına geliyor:

| Modül (namespace) | Subdomain (test ortamı: `tfsfoytest.org.tr`) | Kitle / Amaç |
|---|---|---|
| `Frontend` | kök domain (`tfsfonayliyarismalar.org`) | Halka açık site + üye (fotoğrafçı) self-servis alanı |
| `Administrator` | `eys.*` | TFSF personeli / merkez yönetim (EYS = Elektronik Yönetim Sistemi) |
| `Kurum` | `kurum.*` | Yarışma düzenleyen kurum/dernek portalı |
| `Temsilci` | `temsilci.*` | TFSF bölge temsilcisi portalı |
| `Juri` | `juri.*` | Jüri üyesi portalı (fotoğraf oylama) |
| `Sonuc` | `sonuc.*` | Halka açık sonuç/arşiv mikro-sitesi |
| `Rest` | — | Dahili JSON/AJAX API (dropdown, chunked file upload) |
| `Cron`, `Export`, `Middleware`, `Shared` | — | CLI job'ları, Excel export, paylaşılan referans veri önbelleği |

`Shared` modülü bilinçli olarak route'suz bırakılmış (sadece cache'lenmiş referans veri sağlıyor); `Middleware` ismine rağmen HTTP middleware değil, paylaşılan model mantığı barındırıyor.

**Yeniden yazım açısından çıkarım:** Bu aslında **6 ayrı ön-yüz + tek backend/DB** rewrite'ı — Laravel tarafında `Route::domain()` bazlı gruplama veya panel bazlı ayrı route dosyaları ile karşılanabilir.

### 2.2 Base Controller + `_remap()` deseni

`app/Controllers/*Controller.php` altında her modül için bir taban controller var (`AdministratorController`, `FrontendController`, `JuriController`, `KurumController`, `TemsilciController`, `SonucController`, `RestController`, `SharedController`). Bunlar:
- Daisy template/asset/pagination/dil ayarlarını kurar,
- `initController()` içinde **`loginCheck()`** ile oturum kontrolü yapar (route filter değil, controller içi kontrol),
- `AdministratorController::checkAuthorization($moduleName)` ile rol/izin kontrolü yapar,
- CI4'ün native auto-routing'i yerine **CI3 tarzı `_remap()`** kullanılıyor (`method_exists($this, $method) ? $this->$method(...) : 404`).

### 2.3 Daisy — özel template/asset kütüphanesi

`app/Libraries/Daisy/*` (11 dosya: `Template`, `Document`, `Asset`, `Config`, `Language`, `Pagination`, `Cache`, `Functions`, `Agent`, `AdministratorLogs`, `Database`), CI4'ü saran, CI4'ün kendi `view_cell()` özelliğinden önce yazılmış **elle inşa edilmiş component/slot kompozisyon sistemi**. Controller'lar sayfa bölgelerini (`header`, `mainHeader`, `sidebar`/`aside`, `footer`, `footerJs`) ayrı "partial controller"lara delege eder; `Daisy\Template::render()` bunları tek tek render edip HTML string olarak `$viewData`'ya koyar, sonra modülün ana layout dosyası (`dashboardView.php` / `frontendView.php`) bu string'leri `echo` eder.

Bu sistemin Laravel'de birebir karşılığı yok — **Blade + Tailwind ile baştan tasarlanacak**, port edilmeyecek.

### 2.4 Statik varlıklar ve deploy anti-pattern'i

- `public/` dizini neredeyse boş (sadece `index.php`, `.htaccess`, `favicon.ico`, `robots.txt`) — hiçbir CSS/JS/görsel `public/` altında değil.
- Tüm statik varlıklar **her modülün kendi `Views/assets/` altında tekrarlanmış** (Frontend 2093 dosya, Kurum 634, Sonuc 618, Temsilci 487, Administrator 579, Juri 460 dosya) — aynı DashLite tema/vendor kütüphaneleri modül başına kopyalanmış durumda.
- Bu çalışabiliyor çünkü **proje kökü doğrudan web'e açık** — kök `.htaccess` + `rewrite.php`, Apache DocumentRoot'un proje köküne ayarlı olduğu varsayımıyla, gerçek dosyaları (`ThirdParty/Frontend/Views/assets/css/...`) PHP'yi hiç devreye sokmadan doğrudan serve ediyor.

> ⚠️ **Güvenlik notu:** Bu, standart olmayan ve güvensiz bir CI4 deploy şekli — tüm proje kökü web'e açık. **Laravel rewrite'ta kesinlikle tekrarlanmamalı**; statik varlıklar `public/` (veya Vite build çıktısı) altına taşınmalı.

---

## 3. Route & Controller Envanteri

Routing `app/Config/Routes.php` içinde değil (boş kabuk) — her modülün kendi `Config/Routes.php` dosyasında, `subdomain`/`hostname` route seçenekleriyle tanımlı.

### 3.1 Administrator (`eys`) — 395 satır, en zengin modül

Tekrarlayan `Ekle/Guncelle/Sil/Detay/Listesi` (create/update/delete/detail/list) deseniyle, alanlar:

- **Auth**: `admin-giris` / `admin-cikis`
- **İçerik yönetimi**: Duyurular + kategorileri, FAQ + kategorileri, CMS sayfaları + kategorileri + statik sayfalar, iletişim sayfası ayarları
- **Yarışma işlemleri** (`yarisma-islemleri`) — **çekirdek modül**: liste/detay/ekle/güncelle/sil, **onay iş akışı** (`onay`/`onayla`), ödül listesi, kategori listesi
- **Şikayet & disiplin**: `yarisma-sikayet` (bildirim), `sikayet-karar` (karar/ceza)
- **Referans veri CRUD'ları** (~10+ ayrı controller): yarışma türü, yarışma durumu, katılım iptal sebebi, fotoğraf şartname kuralı, ödül türü, üyelik engel/iptal sebebi, temsilci bölgesi, dernek bilgisi, ülke, şehir
- **Yarışma kontrol** (`yarisma-kontrol`): sunucu/DB kontrolleri, tekrarlanan fotoğraf tespiti
- **Rapor**: genel rapor dashboard + Excel export
- **Üye yönetimi**: üye CRUD, dernek üyeliği bağlama, üye yasaklama, üye işlem logu
- **Kurum/Temsilci/Jüri yönetimi**: kurum, kurum görevlisi, sekreterya, temsilci, jüri CRUD (şifre sıfırlama dahil)
- **Yönetici işlemleri**: admin/personel kullanıcı CRUD, rol/yetki atama, kendi hesap şifre değişimi, admin işlem logu
- **Dil desteği**: i18n yönetimi
- **Uygulama ayarları**: meta bilgi, e-posta ayarları, sistem parametreleri (hostname bazlı)

### 3.2 Frontend (kök domain, `tr`/`en` route grupları)

- Anasayfa, duyuru, SSS, iletişim, CMS sayfa detay, statik sayfa detay
- **Üye alanı** (`uye`/`member`): dashboard, giriş/kayıt/çıkış, şifre sıfırlama (SMS/e-posta/aktivasyon), profil (kişisel bilgi, adres, iletişim, dernek üyeliği, güvenlik, bildirim ayarları, hesap dondurma), kendi **aktif** ve **arşiv** yarışma katılımları
- **Yarışma kataloğu**: aktif/ulusal/uluslararası yarışma listeleri, yarışma detay, katılımcı listesi, **kayıt/başvuru akışı** (kayıt, kayıt-ol, kayıt-kontrol, başarı sayfaları)
- **Kısıtlama kararları**: şikayet sonucu verilen süreli/süresiz yasakların halka açık listesi

### 3.3 Juri (`juri`, `tr`/`en`)

- Giriş/çıkış, şifremi unuttum, profil/şifre değişimi
- **Jüri oylama akışı**: değerlendirilecek yarışma listesi, oylama (+ "distract"/rastgele mod), oy verilen/tüm fotoğraflar, oy güncelleme
- **Etiket bazlı eleme oylaması**: etiket oluştur/sil, fotoğraf ekle/çıkar, oy kaydet (AJAX)

### 3.4 Kurum (`kurum`, `tr`)

- Giriş/çıkış, şifremi unuttum
- Aktif yarışma bilgileri: liste/detay/istatistik/katılımcı listesi, kayıtlı katılımcı ekleme
- Arşiv yarışma bilgileri: liste/detay/istatistik, katılımcı listesi, ödül kazanan listesi + fotoğrafları, sonuç bildirgesi
- Kurum görevlisi & sekreterya profil/şifre self-servis

### 3.5 Temsilci (`temsilci`, `tr`) — Administrator'dan sonra en zengin modül, 94 satır

- Giriş/çıkış, kendi profil/şifre bilgisi
- Aktif/arşiv yarışma bilgileri (Kurum ile benzer)
- **Jüri değerlendirme gözetimi**: jüri değerlendirme tutanağı (+yazdır), yarışma izleme tutanağı (+yazdır)
- **Satın alma ödülleri**: ekleme/silme
- **Sonuç ilanı**: arşiv yarışma sonuç ilanı, en iyi fotoğrafçı ödülü (+silme)
- **Ödül atama**: tekil ve toplu (çoğul) atama
- **Online jüri yönetimi**: eleme turu fotoğraf uygunluk incelemesi, tur yapılandırma/izleme, tur sonlandırma, online jüri ödül ataması

### 3.6 Sonuc (`sonuc`, `tr`/`en`) — halka açık sonuç mikro-sitesi

Yarışma sonuç listesi/detay, katılım listesi, "meraklısına" istatistikler, sonuç bildirge dokümanı.

### 3.7 Rest — dahili JSON/AJAX API (harici tüketiciler için değil)

- Ülke → şehir cascading dropdown
- Yarışma/kategori bilgi lookup
- **Chunked fotoğraf/dosya yükleme** endpoint'leri (`writable/yarisma-katilim-tmp`'ye yazıyor)
- Temsilci arşiv görünümleri için AJAX katılımcı/fotoğraf verisi

### 3.8 Cron — CLI-only

- E-posta kuyruğu işleme (`AWSCron`)
- Üye detay verisi yenileme
- **Yarışma arşivleme pipeline'ı** (`ya-cron`): durum güncelleme → arşivleme → dizin bilgisi → resim resize → arşiv dosyası/ZIP hazırlama → online jüri işleme

### 3.9 Export — Excel export (subdomain `kurum`)

Temsilci için ödül kazananların Excel export'u (PhpSpreadsheet).

---

## 4. Yetkilendirme & Güvenlik

- **CI4 filter mekanizması hiç kullanılmıyor**: `app/Filters/` boş, `app/Config/Filters.php` sadece stok alias'lar tanımlıyor, `globals.before`/`globals.after` etkin filtre içermiyor.
- **Auth, rol kontrolü ve (eksik) CSRF tamamen controller içinde, imperatif olarak yapılıyor.**
- **5 paralel ve birbirinden bağımsız oturum tabanlı auth sistemi** var — her modülün kendi `Libraries/Auth.php`'si (Administrator, Frontend, Juri, Kurum, Temsilci), `Config\Services::<Site>Auth()` üzerinden expose ediliyor. Bu 5 sistem birbirinden habersiz çalışıyor.
- **CSRF etkin değil**: `App.php` içinde token/cookie adı tanımlı ama CSRF filtresi `globals.before`'a hiç bağlanmamış — pratikte CSRF koruması yok.
- **Hardcoded sırlar (repo içinde açık metin):**
  - MailerSend API key, ~10 farklı controller dosyasında birebir hardcoded (`'api_key' => 'mlsn.3be7b6b65e2aff...'`)
  - NetGSM SMS hesap no/şifresi kısmen hardcoded, kısmen env
  - `app/Config/Database.php` içinde dev DB kullanıcı adı/şifresi (`dev10`/`dev10`) doğrudan kaynak kodda
- Google reCAPTCHA v2 (checkbox) formlarda (üye kaydı, yarışma kaydı, iletişim formu, 4 panel login formu) doğrulama kuralı olarak zorunlu kılınmış; server-side `secret` doğrulamasının gerçekten yapılıp yapılmadığı ayrıca teyit edilmeli.

**Rewrite'a doğrudan yansıması gereken kritik noktalar:**
1. 5 auth sistemi → Laravel guard/policy yapısına konsolide edilmeli (panel başına bir guard: admin, kurum, temsilci, juri, member).
2. CSRF, Laravel'de varsayılan açık — bu mevcut sistemdeki bir eksikliğin otomatik giderilmesi anlamına geliyor, sadece bilinçli bir "gap" olarak not düşülüyor.
3. Tüm sırlar `.env`'e taşınmalı **ve migrasyon sonrası rotate edilmeli** (repo'da açık metin olarak durdukları için sızıntı riski zaten gerçekleşmiş sayılmalı).

---

## 5. Veri Modeli

### 5.1 Genel yaklaşım

- **CI4 Model/Entity/Migration hiç kullanılmıyor.** `extends \App\Models\BaseModel` (özel, `$this->db->table()` query builder'ını saran ince bir wrapper) — `allowedFields`, CI4 validation kuralları yok.
- **Entity katmanı yok** — tüm veri erişimi düz ilişkisel dizi (`getRowArray()`/`getResultArray()`) döner; domain nesnesi yok, saf "array in, array out" mimarisi.
- **Migration/Seed dizinleri boş** (`.gitkeep` dışında) — şema DBA tarafında/manuel yönetiliyor, koda yansımıyor. Tek istisna: `filez/maraton-calismasi/db.sql` (manuel bir `ALTER`/`CREATE TABLE` script'i), şema konvansiyonunu doğruluyor: `varchar(36)` UUID PK, InnoDB, utf8.
- **Validasyon Model'de değil, Controller'da** inline `$validation->setRule(...)` ile yapılıyor.
- **Tüm PK/FK'ler UUID string** (`Ramsey\Uuid\Uuid::uuid4()` ile PHP tarafında üretiliyor), auto-increment değil (bazı pivot/junction tablolarda `auto_uuid` surrogate anahtar istisna).
- **Çok dilli içerik deseni**: `<tablo>` (dil-bağımsız alanlar) + `<tablo>_detay` (her `language_uuid` için bir satır, çevrilebilir alanlar) + merkezi `language` tablosu (`uuid, name, code, locale, image, html_dir, directory, sort_order, status`). Bu, `app/Language/{en,tr}` UI-string dosyalarından **ayrı**, veritabanı seviyesinde bir i18n yapısı.

### 5.2 Tablo envanteri (özet)

**Yarışma (Competition) alanı — çekirdek:**

| Tablo(lar) | Amaç |
|---|---|
| `yarisma` / `yarisma_detay` | Yarışma; onay numaraları (`tfsf_approve_no`, `fiap/gpu/psa_approve_no`), kurum/temsilci/sekreterya bağlantısı, tarih alanları, durum |
| `yarisma_kategori` / `_detay` | Kategori; teknik gereksinimler (dosya boyutu/DPI/kenar), demografik uygunluk (cinsiyet, yaş aralığı) |
| `yarisma_to_yarisma_kategori` | Yarışma↔Kategori (many-to-many pivot) |
| `yarisma_kategori_to_juri` | Kategori↔Jüri ataması (many-to-many pivot) |
| `yarisma_odul`, `yarisma_to_yarisma_odul` | Ödül tanımı ve kategoriye atanması, `ref_yarisma_odul` lookup'a FK |
| `yarisma_sikayet_bildirimi`, `yarisma_sikayet_karar` / `_karar_detay` | Şikayet bildirimi ve çok dilli karar/ceza |

**Katılım/Başvuru (Submission):**

| Tablo(lar) | Amaç |
|---|---|
| `yarisma_katilim` | Başvuru başlığı — üye × kategori başına bir satır |
| `yarisma_katilim_detay` | Başvurulan fotoğraflar (bir başvuru → çok fotoğraf) |
| `yarisma_katilim_dosya` | Ek belgeler |
| `yarisma_katilim_iptal`, `ref_yarisma_katilim_iptal_sebebi` | Başvuru iptali + sebep lookup |
| `yarisma_katilim_kayitli_kullanicilar` | Ön-kayıt (başvurudan önce yarışmaya kayıt), sıra numarası |
| `yarisma_katilim_kayit_belge` | Kayıt destekleyici belgeleri |

**Arşiv alanı — önemli mimari desen:**

Değerlendirme başladığında, cron pipeline'ı **tüm canlı şemayı `arsiv_` önekiyle paralel bir tablo setine kopyalıyor**: `arsiv_yarisma(_detay)`, `arsiv_yarisma_kategori(_detay)`, `arsiv_yarisma_katilim(_detay)`, `arsiv_yarisma_katilim_dosya`, `arsiv_yarisma_odul`, `arsiv_yarisma_tutanak`, `arsiv_yarisma_en_iyi_fotografci`, vb.

**Jüri oylama/skorlama SADECE arşiv tablolarına karşı çalışıyor** (canlı `yarisma_*` tablolarına değil):

| Tablo | Amaç |
|---|---|
| `arsiv_yarisma_oylama_tur` | Oylama turu (tur tipi, başlangıç/bitiş, `odul_sirasi_cut_off`, `kabul_sirasi_cut_off`) |
| `arsiv_yarisma_oylama_eleme` | Eleme aşaması takibi |
| `arsiv_yarisma_oylama` | Jüri üyesi × fotoğraf × tur başına bir satır, `juri_puan` |
| `arsiv_yarisma_oylama_final` | Toplam puan (`toplam_puan`), ödül türü sınıflandırması |
| `juri_etiket`, `juri_etiket_fotograf` | Jüri "etiket/flag" notları |

> **Rewrite önerisi:** Bu arşiv-kopyalama deseni MySQL-dönemi bir çözüm; Laravel rewrite'ta tek tablo seti + `phase`/`status` kolonu (gerekirse soft-archiving) ile değiştirilmeli, tablo çiftlemesi port edilmemeli.

**Kişi/hesap alanı:**

| Tablo(lar) | Amaç |
|---|---|
| `tfsf_users` / `_detail` | Üye (fotoğrafçı) — TC kimlik no, e-posta, aktivasyon, durum |
| `tfsf_users_bildirim_ayarlari`, `_guvenlik_ayarlari`, `_engel`, `_dernek` | Üye bildirim/güvenlik ayarları, yasak, dernek bağlantısı |
| `kurum` / `_detay`, `kurum_gorevli` / `_detay` | Kurum ve kurum görevlisi |
| `temsilci` / `_detay`, `ref_temsilci_bolge` | Temsilci ve bölge lookup |
| `juri` / `_detay` | Jüri üyesi |
| `users` / `_detail` / `_authorization` / `_logs` | Sistem yöneticisi hesapları (üye hesaplarından tamamen ayrı), rol/yetki ve audit log |

**CMS/içerik alanı:** `announcement(_detail)` + kategori (opsiyonel `yarisma_uuid` bağlantısı), `page(_detail)` + kategori, `simple_page`, `faq(_detail)` + kategori, `contact_settings`, `setting` (EAV, `Daisy\Config` ile yönetiliyor).

**Referans/lookup alanı (`ref_*`):** ülke, şehir, yarışma türü, yarışma durumu, ödül türü, fotoğraf şartname kuralı, katılım iptal sebebi, üyelik engel/iptal sebebi, temsilci bölgesi, dernek bilgisi, üye işlem logu — hepsi PHP tarafında `app/Config/TFSF/*Enum.php` sabitleriyle eşleşiyor.

**Operasyonel:** `jobs` (genel async iş kuyruğu), `aws_mail_queue` (AWS e-posta kuyruğu).

### 5.3 Çıkarılan varlık-ilişki modeli (özet)

```
Kurum ──organize eder──> Yarışma ──çoktan-çoğa──> Kategori ──çoktan-çoğa──> Jüri
                            │                         │
                            │                         └──çoktan-çoğa──> Ödül (ref_yarisma_odul)
                            │
                            ▼
                     Üye ──ön-kayıt──> Kayıtlı Katılımcı
                            │
                            ▼
                   Katılım (Üye × Kategori) ──çoka──> Fotoğraf (Katılım Detay)
                            │                                    │
                     (opsiyonel: Kurum/Temsilci onayı)           │
                            │                                    ▼
                            ▼                          [Değerlendirme başlar]
                     Arşive kopyalanır (cron) ──────────────────┘
                            │
                            ▼
              Oylama Turu ──her tur için──> Jüri Puanı (fotoğraf başına)
                            │
                            ▼
                  Toplam Puan → Ödül Sınıflandırması (sergileme/kabul/ödül)
                            │
                            ▼
                    Sonuç İlanı (Sonuc modülü, halka açık)
```

Ayrıca: Şikayet → Karar (disiplin) → Kısıtlama kararı (halka açık liste) + Üye Yasağı; Duyuru/Sayfa/SSS bağımsız CMS içeriği (opsiyonel yarışma bağlantısı).

---

## 6. İş Akışları / Durum Makineleri

### 6.1 Yarışma yaşam döngüsü (`yarisma.yarisma_durum_uuid`)

```
TFSFOnayliBekleniyor ──(admin onayı: yarismaOnay/yarismaOnayla)──> yarismaBasladi
        │                                                              │
        │ (sadece bu durumda düzenlenebilir/                          │ (katılım kabul edilir)
        │  ödül eklenebilir)                                          ▼
        │                                                       degerlendirme
        │                                                              │ (arşive kopyalanır,
        │                                                              │  online jüri uygulanabilir)
        │                                                              ▼
        │                                                     sonuclarAciklandi
        │                                                              │
        │                                                              ▼
        │                                                      arsiveAktarildi (final)
        │
        └────────────────────> yarismaIptalEdildi (terminal, alternatif dal)
```

### 6.2 Katılım onay modları (`yarisma.katilim_onay`)

Bir yarışma, üye başvurusunun geçerli sayılması için 4 moddan birini seçebilir: onay süreci yok / temsilci+kurum onayı / sadece temsilci / sadece kurum.

### 6.3 Üye durumları ve tipleri

- **Durum**: aktivasyon bekleniyor (90) → aktif (1) / engelli-yasaklı (0)
- **Tip**: normal üye / dernek üyesi / dernek üyesinin üyeleri / kayıtlı katılımcı (kurumun toplu eklediği özel kategori)

### 6.4 Ödül türleri

`satınAlma` (satın alma/onur ödülü tarzı), `sergileme` (exhibition), `kabul` (acceptance) — **tamamı tanınma bazlı, parasal değil**.

### 6.5 Uçtan uca akışlar

1. **Yarışma oluşturma & onay**: Kurum/admin yarışma oluşturur → `TFSFOnayliBekleniyor` → TFSF admin onaylar → yarışma görünür/başvurulabilir hale gelir.
2. **Halka açık başvuru**: Üye kayıt olur/giriş yapar (reCAPTCHA) → aktif yarışmaları listeler → belirli bir yarışmaya kayıt olur → chunked upload REST endpoint'i ile fotoğraf yükler → yapılandırmaya göre kurum/temsilci onayı gerekebilir.
3. **Şikayet & disiplin süreci**: Şikayetler admin tarafından incelenir (`YarismaSikayet`) ve karara bağlanır (`YarismaSikayetKarar`) → süreli/süresiz yasaklar halka açık listelenir (`kisitlama-kararlari`) ve `UyeYasak` ile uygulanır.
4. **Jüri değerlendirme**: Yarışma `degerlendirme` durumuna geçince jüri üyeleri Juri subdomain'inde giriş yapıp kategori bazında oylar ("distraction mode" varyantı dahil); çok turlu yarışmalar için etiket bazlı eleme mekanizması var; süreç Temsilci tarafından (`TemsilciJuriIslemleri`) tur yapılandırma, uygun/uygun-olmayan fotoğraf triyajı ve tur sonlandırma ile gözetleniyor.
5. **Sonuç & kapanış**: Temsilci jüri/izleme tutanaklarını finalize eder, ödülleri atar (tekil/toplu, satın alma dahil, en iyi fotoğrafçı ayrımı), sonuç ilanını yayınlar → sonuçlar `sonuclarAciklandi` durumunda Sonuc subdomain'inde görünür olur.
6. **Arşivleme**: CLI cron pipeline'ı (`Cron\YarismaArsiv`) durumu geçirir, veriyi arşiv şemasına taşır, görselleri resize eder, indirilebilir ZIP arşiv oluşturur, online jüri son durumunu işler — sonrasında kurum/temsilci/kurum panelleri yarışmayı "aktif" yerine "arşiv" görünümünde gösterir.
7. **Raporlama**: Admin-only toplu rapor dashboard + Excel export; kurum tarafında ödül kazananların Excel export'u.

---

## 7. Frontend / View Katmanı

### 7.1 Yapı

`app/Views/` sadece CI4'ün stok hata sayfası şablonlarını içeriyor — **gerçek görünümler `ThirdParty/<Modül>/Views/` altında**. Her modülde tekrar eden alt yapı:

- `Common/` — layout parçaları: `headerView.php` (`<head>`), `mainHeaderView.php` (topbar), `sidebarView.php`/`asideView.php` (nav), `footerView.php`, `footerJsView.php` (JS include + flash mesaj)
- `<Özellik>/` klasörleri, domain varlıklarını yansıtıyor: `xxxListView.php`, `xxxEkleView.php`, `xxxDetayView.php`, `xxxGuncelleView.php`
- `Mail/` — HTML e-posta şablonları (dil bazlı alt klasörler)
- Modül başına tek ana layout: `dashboardView.php` (Administrator/Juri/Kurum/Temsilci — giriş yapılmış panel kabuğu) veya `frontendView.php` (Frontend — halka açık kabuk)
- `assets/` — modül başına tekrarlanan tam statik varlık ağacı (bkz. §2.4)

### 7.2 Templating yaklaşımı

Düz PHP view'lar (alternatif syntax `<?php if(): ?>...<?php endif; ?>`), Twig/Blade yok. Sayfa kompozisyonu `Daisy\Template::render()` ile yapılıyor (bkz. §2.3). Auth/rol config'i persona başına `app/Config/Daisy/{Administrator,Frontend,Juri,Kurum,Temsilci}AuthProperties.php` dosyalarında.

### 7.3 Örnek sayfa yapıları

- **Admin liste sayfası** (`yarismaListView.php`): header/mainHeader/sidebar partial'ları + filtre formu + DashLite `.nk-tb-list` (aslında `<table>` değil, `<div>` tabanlı) tablo + durum enum'una göre koşullu satır aksiyonları (detay/güncelle/onayla/sil) + custom pagination.
- **Halka açık sayfa** (`frontendView.php`): Splide.js slider + aynı "div-grid table" desenli yarışma listesi.
- **Form sayfası** (`yarismaEkleView.php`, 903 satır): Bootstrap `nav-tabs` ile organize edilmiş form; **her aktif dil için bir sekme** (title/description/spec/SEO alanları için); validasyon hataları ilgili sekme linkinde kırmızı rozet olarak gösteriliyor; kaydetme, normal submit yerine JS ile tetikleniyor (`$('#form_action').submit()`).
- **Login sayfası**: bağımsız, shared partial kullanmıyor; ortalanmış kart form, flash hata gösterimi.

### 7.4 Çoklu dil desteği

- UI string dosyaları: `app/Language/{en,tr}` (global) + her modülün kendi `Language/{en,tr}` klasörü.
- **Dil, routing seviyesinde de gömülü**: `$routes->group('tr', ...)` / `$routes->group('en', ...)` → `/tr/...`, `/en/...` farklı adlandırılmış route'lar üretiyor.
- **İçerik seviyesinde de çok dilli**: yarışma başlık/açıklama/SEO alanları, admin formunda dil başına bir sekme olarak, DB'deki `language` tablosuna bağlı (bkz. §5.1).

### 7.5 Dosya/medya yönetimi

- `filemanager/` = Responsive FileManager v9.14.0 (üçüncü parti kütüphane, lisans/bağımlılık olarak rewrite'ta değiştirilmeli — örn. Laravel medya kütüphanesi + Tailwind UI ile).
- `filez/` medya değil, **operasyonel notlar** klasörü: arşiv çalışma logları, cron bilgi notları, değişiklik geçmişi, systemd service dosyaları — rewrite'a doğrudan girdi değil ama deploy/ops bağlamı için faydalı.
- Gerçek yüklenen medya `writable/` altında: `writable/uploads/`, `writable/yarisma-katilim(-tmp)/`, `writable/yarisma-arsiv-{orjinal,buyuk,kucuk}/`, `writable/yarisma-dis-kaynak/`, `writable/yarisma-zip/`. Path helper'ları `app/Libraries/Daisy/Asset.php` içinde merkezi.

---

## 8. Entegrasyonlar

| Entegrasyon | Kullanım | Not |
|---|---|---|
| **MailerSend** | Transactional e-posta (kayıt, şifre sıfırlama, aktivasyon, iletişim formu, kimlik bilgisi dağıtımı) — ~10 controller'da doğrudan inline çağrı | API key hardcoded, tek bir env değişkenine indirgenmeli |
| **Amazon SES / Brevo SMTP** | PHPMailer alt sınıfları (`app/Libraries/Amazon`, `app/Libraries/BrevoApi`) | MailerSend ile örtüşen/belirsiz kullanım — rewrite öncesi hangisinin canlı olduğu doğrulanmalı |
| **NetGSM** | Şifre sıfırlama SMS akışı | Kısmen hardcoded, kısmen env kimlik bilgisi |
| **Google reCAPTCHA v2** | Üye kaydı, yarışma kaydı, iletişim formu, 4 panel login formu | Server-side `secret` doğrulaması teyit edilmeli |
| **PhpSpreadsheet** | Admin rapor Excel export, temsilci ödül kazananları Excel export | — |
| **Responsive FileManager** | TinyMCE görsel seçici + genel dosya yönetimi | Üçüncü parti lisans/bağımlılık |
| Ödeme / e-Devlet / e-imza | — | **Yok** — sistemde bulunmuyor |

---

## 9. Laravel Rewrite İçin Notlar

Keşif sırasında ortaya çıkan, mimari tasarım aşamasında doğrudan girdi olacak somut bulgular:

1. **5 paralel auth sistemi → tek guard/policy yapısı.** Her subdomain'in kendi bağımsız `Libraries/Auth.php`'si var; Laravel'de panel başına guard (admin, kurum, temsilci, juri, member) + policy/role modeliyle konsolide edilmeli.
2. **CI4 filter hiç kullanılmamış** — auth/yetkilendirme baştan middleware olarak tasarlanacak; port edilecek filtre kodu yok, sıfırdan yazılacak.
3. **CSRF varsayılan açık bırakılmalı** (Laravel'de zaten öyle) — mevcut sistemin bilinen bir eksikliğinin otomatik giderilmesi.
4. **Sırların taşınması + rotasyonu**: MailerSend API key (~10 dosya), NetGSM kimlik bilgileri, DB kimlik bilgileri — `.env`'e taşınmalı; repo'da açık metin olarak durdukları için **rotasyon şart**.
5. **~25+ neredeyse birebir aynı referans-veri CRUD controller'ı** (ülke, şehir, yarışma türü/durumu, ödül türü, yasak/iptal sebepleri...) — Laravel'de tek bir generic/resource abstraction (örn. ortak bir trait/resource controller veya Filament-benzeri bir yaklaşım) güçlü aday.
6. **Daisy template sistemi port edilmeyecek** — Blade + Tailwind ile baştan tasarlanacak; mevcut sistem sadece "hangi bölgeler var, hangi component'ler tekrarlanıyor" referansı olarak kullanılmalı.
7. **Rest modülü, harici tüketiciler için genel bir API değil** — dahili AJAX/upload ihtiyaçları için. Rewrite'ta bu böyle mi kalacak yoksa versiyonlanmış gerçek bir API'ye mi dönüşecek, ayrı bir karar.
8. **Subdomain bazlı çoklu-site mimarisi** için net bir strateji gerekiyor: `Route::domain()` ile tek Laravel uygulamasında mı, yoksa panellerin ayrı uygulamalara/modüllere mi bölüneceği — bu, sonraki mimari tasarım görüşmesinin ilk kararlarından biri olmalı.
9. **Arşiv-kopyalama deseni tekilleştirilmeli** — `arsiv_*` tablo çiftlemesi yerine tek şema + `phase`/durum kolonu.
10. **Statik varlık/deploy modeli düzeltilmeli** — proje kökünün web'e açık olması ve modül başına varlık kopyalanması, `public/` + build pipeline (Vite/Tailwind) ile değiştirilmeli.

---

## 10. Ekler

### 10.1 Modül → Controller sayısı (kaba envanter)

| Modül | Controller sayısı (yaklaşık) |
|---|---|
| Administrator | 44 |
| Frontend | 19 (+4 layout partial) |
| Juri | 4 (+ partial'lar) |
| Kurum | 4 (+ partial'lar) |
| Temsilci | 7 (+ partial'lar) |
| Sonuc | 1 |
| Rest | 4 |
| Cron | 3 |
| Export | 1 |

### 10.2 Önemli dosya yolları (rewrite sırasında tekrar bakılacak)

| Amaç | Yol |
|---|---|
| İş kuralı enum'ları | `app/Config/TFSF/*.php`, `ThirdParty/Shared/Config/YarismaDurumEnumList.php` |
| Çekirdek yarışma modeli | `ThirdParty/Administrator/Models/Yarisma/YarismaModel.php`, `YarismaKategoriModel.php`, `YarismaOdulModel.php` |
| Başvuru/fotoğraf modeli | `ThirdParty/Frontend/Models/Yarisma/YarismaKayitModel.php`, `YarismaKatilimModel.php`, `YarismaKayitOlModel.php` |
| Jüri oylama motoru | `ThirdParty/Cron/Models/YarismaOylamaCronModel.php`, `ThirdParty/Juri/Models/JuriYarismaModel.php` |
| Arşiv pipeline | `ThirdParty/Cron/Models/YarismaArsivCronModel.php`, `YarismaArsivModel.php` |
| Üye/auth modeli | `ThirdParty/Frontend/Models/Uye/UyeAuthModel.php`, `ThirdParty/Administrator/Models/Uye/UyeModel.php` |
| Kurum/Temsilci/Jüri hesap modelleri | `ThirdParty/*/Models/{Kurum,Temsilci,Juri}*.php` |
| Çoklu-site config | `configGeneral.php`, `configPathURL.php`, `configVersion.php` (proje kökünde, `app/` dışında) |
| Datatable/liste altyapısı | `app/Libraries/Datatable.php` |
| Job/queue altyapısı | `app/Libraries/Jobs.php`, `app/Models/JobsModel.php` |

Tüm ham tablo envanterini yeniden üretmek için: `grep -rhoE "->table\('[a-zA-Z0-9_ ]+'\)" ThirdParty/ app/`

### 10.3 Sonraki adım

Bu doküman onaylandıktan sonra, **Laravel + PHP 8.5 + Tailwind mimarisi tasarımı** ayrı bir görüşme/planlama sürecinde ele alınacak — bu aşamada tasarım bileşenleri de dahil olmak üzere sistem baştan tasarlanacak (bu dokümandaki mevcut yapı, o tasarımın referans/başlangıç noktası, birebir taşınacak bir şablon değil).
