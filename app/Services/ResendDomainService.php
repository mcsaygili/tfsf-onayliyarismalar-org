<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ResendDomainService
{
    private const CACHE_KEY = 'resend:sending-domain-status';

    /** @return array<string, mixed> */
    public function status(bool $refresh = false): array
    {
        if (! $refresh && Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        $domain = (string) config('services.resend.sending_domain');
        $key = (string) config('services.resend.key');
        if (blank($domain) || blank($key)) {
            return ['domain' => $domain, 'status' => 'not_configured', 'spf_dkim' => false, 'dmarc' => false, 'checked_at' => now()];
        }

        try {
            $response = Http::withToken($key)->acceptJson()->timeout(8)->get('https://api.resend.com/domains');
            $response->throw();
            $record = collect($response->json('data', []))->firstWhere('name', $domain);
            if (! $record) {
                throw new RuntimeException('Gönderim domaini Resend hesabında bulunamadı.');
            }

            $dmarc = $this->hasDmarc($domain);
            $status = [
                'domain' => $domain,
                'status' => $record['status'] ?? 'unknown',
                'spf_dkim' => ($record['status'] ?? null) === 'verified',
                'dmarc' => $dmarc,
                'region' => $record['region'] ?? null,
                'capabilities' => $record['capabilities'] ?? [],
                'checked_at' => now(),
            ];
        } catch (Throwable $exception) {
            $status = ['domain' => $domain, 'status' => 'error', 'spf_dkim' => false, 'dmarc' => $this->hasDmarc($domain), 'error' => $exception->getMessage(), 'checked_at' => now()];
        }

        Cache::put(self::CACHE_KEY, $status, now()->addMinutes(15));

        return $status;
    }

    public function recommendedDmarcRecord(): string
    {
        $reportAddress = (string) config('services.resend.dmarc_report_address');

        return 'v=DMARC1; p=none;'.($reportAddress ? ' rua=mailto:'.$reportAddress.';' : '');
    }

    private function hasDmarc(string $domain): bool
    {
        $records = @dns_get_record('_dmarc.'.$domain, DNS_TXT);

        return collect(is_array($records) ? $records : [])->contains(
            fn (array $record) => str_starts_with((string) ($record['txt'] ?? ''), 'v=DMARC1;')
        );
    }
}
