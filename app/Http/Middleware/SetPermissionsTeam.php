<?php

namespace App\Http\Middleware;

use App\Enums\Module;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Belirli rota grupları için Spatie'nin aktif "team" context'ini sabit bir
 * App\Enums\Module'e ayarlar — EYS bir gün başka bir modülün (ör. Institution)
 * verisini yönetirken, o modülün granüler izinlerinin (ör.
 * institution.staff.manage) doğru team'de kontrol edilmesini sağlar.
 *
 * Kullanım: ->middleware('team:Institution') — permission middleware'inden
 * ÖNCE çalışmalı.
 */
class SetPermissionsTeam
{
    public function __construct(private PermissionRegistrar $registrar) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $resolved = null;
        foreach (Module::cases() as $m) {
            if ($m->name === $module) {
                $resolved = $m;
                break;
            }
        }

        if ($resolved !== null) {
            $this->registrar->setPermissionsTeamId($resolved->value);
            $request->attributes->set('active_module', $resolved);
        }

        return $next($request);
    }
}
