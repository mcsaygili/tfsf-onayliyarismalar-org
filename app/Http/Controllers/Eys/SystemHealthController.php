<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\NotificationDeliveryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __invoke(): View
    {
        $checks = [];
        try {
            DB::select('select 1');
            $checks['database'] = ['status' => 'ok', 'detail' => 'Veritabanı bağlantısı çalışıyor.'];
        } catch (\Throwable $exception) {
            $checks['database'] = ['status' => 'error', 'detail' => $exception->getMessage()];
        }
        $checks['storage'] = ['status' => is_writable(storage_path('app')) ? 'ok' : 'error', 'detail' => is_writable(storage_path('app')) ? 'Özel dosya alanı yazılabilir.' : 'Storage yazma izni bulunmuyor.'];
        try {
            $waitingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $checks['queue'] = ['status' => $failedJobs > 0 ? 'warning' : 'ok', 'detail' => $waitingJobs.' bekleyen, '.$failedJobs.' başarısız iş.'];
        } catch (\Throwable $exception) {
            $checks['queue'] = ['status' => 'error', 'detail' => 'Kuyruk tabloları okunamadı: '.$exception->getMessage()];
        }
        $checks['mail'] = ['status' => filled(config('mail.default')) ? 'ok' : 'warning', 'detail' => 'Aktif sürücü: '.(config('mail.default') ?: 'tanımsız')];

        try {
            $deliveries = NotificationDeliveryLog::latest('attempted_at')->limit(20)->get();
        } catch (\Throwable) {
            $deliveries = collect();
        }

        return view('eys.system-settings.health', [
            'checks' => $checks,
            'deliveries' => $deliveries,
        ]);
    }
}
