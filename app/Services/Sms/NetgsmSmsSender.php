<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NetGSM XML API göndericisi. Aktifleştirme: SMS_DRIVER=netgsm +
 * config/services.php sms.netgsm kimlik bilgileri (usercode/password).
 *
 * - Gönderim: sms_url'e (varsayılan api.netgsm.com.tr/sms/send/xml) XML POST.
 * - msgheader boşsa usercode gönderici başlığı olarak kullanılır.
 * - Kimlik eksikse gönderim atlanır, hata durumunda yalnız warning loglanır —
 *   SMS başarısızlığı uygulamayı asla kırmaz.
 */
class NetgsmSmsSender implements SmsSender
{
    /** NetGSM yanıt kodları → insan-okur açıklama (loglama için). */
    private const ERROR_CODES = [
        '20' => 'Mesaj metni/karakter sınırı sorunu',
        '30' => 'Geçersiz kimlik bilgisi veya API erişim/IP kısıtı',
        '40' => 'Gönderici başlığı (msgheader) sistemde tanımlı değil',
        '50' => 'İYS kontrollü gönderim reddi (abone)',
        '51' => 'İYS marka bilgisi bulunamadı',
        '70' => 'Hatalı veya eksik parametre',
        '80' => 'Gönderim sınır aşımı',
        '85' => 'Mükerrer gönderim sınır aşımı',
    ];

    /** @param array{usercode?:string,password?:string,header?:string,sms_url?:string,balance_url?:string} $config */
    public function __construct(private readonly array $config) {}

    public function send(string $to, string $message): bool
    {
        if (blank($this->config['usercode'] ?? null) || blank($this->config['password'] ?? null)) {
            Log::warning('[SMS:netgsm] kimlik bilgisi eksik — gönderim atlandı.', ['to' => $to]);

            return false;
        }

        $no = $this->normalize($to);
        if ($no === null) {
            Log::warning('[SMS:netgsm] geçersiz numara — gönderim atlandı.', ['to' => $to]);

            return false;
        }

        try {
            $response = Http::withBody($this->buildXml($no, $message), 'text/xml; charset=UTF-8')
                ->post($this->config['sms_url'] ?? 'https://api.netgsm.com.tr/sms/send/xml');

            return $this->interpret(trim($response->body()), $no);
        } catch (\Throwable $e) {
            Log::warning('[SMS:netgsm] istek başarısız: '.$e->getMessage(), ['to' => $no]);

            return false;
        }
    }

    /** Gönderim XML gövdesini kurar (NetGSM sms/send/xml şeması, 1:n). */
    private function buildXml(string $no, string $message): string
    {
        $usercode = $this->config['usercode'];
        $password = htmlspecialchars($this->config['password'], ENT_XML1, 'UTF-8');
        $header = htmlspecialchars($this->config['header'] ?: $usercode, ENT_XML1, 'UTF-8');
        // CDATA içinde tek yasak dizi "]]>" — kırpılır.
        $msg = str_replace(']]>', ']]&gt;', $message);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mainbody>
    <header>
        <company dil="TR">Netgsm</company>
        <usercode>{$usercode}</usercode>
        <password>{$password}</password>
        <type>1:n</type>
        <msgheader>{$header}</msgheader>
    </header>
    <body>
        <msg><![CDATA[{$msg}]]></msg>
        <no>{$no}</no>
    </body>
</mainbody>
XML;
    }

    /** Yanıtın ilk token'ına göre başarı/hata değerlendirir ve loglar. */
    private function interpret(string $body, string $no): bool
    {
        $code = strtok($body, " \n");

        if (in_array($code, ['00', '01', '02'], true)) {
            Log::info('[SMS:netgsm] gönderildi', ['to' => $no, 'response' => $body]);

            return true;
        }

        Log::warning('[SMS:netgsm] sağlayıcı hatası', [
            'to' => $no,
            'code' => $code,
            'meaning' => self::ERROR_CODES[$code] ?? 'Bilinmeyen kod',
            'response' => $body,
        ]);

        return false;
    }

    /**
     * Telefonu NetGSM'in beklediği 10 haneli yerel formata (5XXXXXXXXX) çevirir.
     * Geçersiz numarada null.
     */
    private function normalize(string $to): ?string
    {
        $digits = preg_replace('/\D/', '', $to);

        if (str_starts_with($digits, '90')) {
            $digits = substr($digits, 2);
        }
        $digits = ltrim($digits, '0');

        return (strlen($digits) === 10 && str_starts_with($digits, '5')) ? $digits : null;
    }
}
