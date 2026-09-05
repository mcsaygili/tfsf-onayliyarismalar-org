<?php

namespace App\Http\Middleware;

use App\Services\PanelAccountAccess;
use App\Services\PanelSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelSession
{
    public function __construct(private PanelAccountAccess $access, private PanelSession $sessions) {}

    public function handle(Request $request, Closure $next, string $guardName): Response
    {
        $guard = Auth::guard($guardName);
        if (! $guard->check()) {
            return $next($request);
        }

        // Refresh account and relationships before any panel permission check.
        $account = $guard->getProvider()->retrieveById($guard->id());
        $reason = $account ? $this->access->denialReason($account) : __('auth.account_inactive');
        if ($account && $reason === null) {
            if ($guard->viaRemember()) {
                $cookieHash = explode('|', (string) $request->cookies->get($guard->getRecallerName()))[2] ?? '';
                if ($cookieHash === '' || (! hash_equals($guard->hashPasswordForCookie($account->getAuthPassword()), $cookieHash)
                    && ! hash_equals($account->getAuthPassword(), $cookieHash))) {
                    $reason = __('auth.session_expired');
                }
            }
            if (! $this->sessions->matches($request->session(), $account, $guardName)) {
                $reason = __('auth.session_expired');
            }
        }
        if ($reason === null) {
            $guard->setUser($account);

            return $next($request);
        }

        // Do not rotate every device's remember token when rejecting one old session.
        $guard->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => $reason], 403)
            : redirect()->route($guardName === 'web' ? 'login' : $guardName.'.login')->withErrors(['email' => $reason]);
    }
}
