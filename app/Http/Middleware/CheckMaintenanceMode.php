<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceMode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bakım Modu — EYS "Sistem Ayarları > Bakım Modu" üzerinden bir subdomain
 * (institution/temsilci/juri/uye) için açıldığında, o subdomain'deki HER
 * istek (guest + authenticated, tümü) tek bir bakım sayfası döner; oturum
 * açık bir kullanıcı varsa önce zorla çıkış yaptırılır. EYS bu middleware'e
 * hiçbir route'ta tabi değil — admin bakım modunu her zaman kapatabilmeli.
 *
 * ÖNEMLİ: bootstrap/app.php'de prependToPriorityList() ile bu middleware'in
 * Authenticate'ten ÖNCE çalışması garanti ediliyor — aksi halde Laravel'in
 * $middlewarePriority listesi 'auth:*' varken bunu sessizce arkaya atıyor
 * (bkz. o dosyadaki yorum).
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! MaintenanceMode::isEnabledFor($module)) {
            return $next($request);
        }

        $guard = MaintenanceMode::guardFor($module);

        if (Auth::guard($guard)->check()) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $maintenance = MaintenanceMode::query()->where('module', $module)->first();

        return response()->view("{$module}.maintenance", [
            'message' => $maintenance?->message,
        ], 503);
    }
}
