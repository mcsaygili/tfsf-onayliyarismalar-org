<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\NotificationDeliveryLog;
use App\Services\SystemHealthService;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __invoke(SystemHealthService $health): View
    {
        $checks = $health->checks();

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
