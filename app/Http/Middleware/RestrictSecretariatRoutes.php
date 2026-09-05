<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictSecretariatRoutes
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user('institution')->isSecretariat()) {
            abort_unless($request->routeIs('institution.dashboard', 'institution.operations.*', 'institution.password.*', 'institution.secretariat.*', 'institution.registrations.*', 'institution.participant-submissions.*'), 403);
        }

        return $next($request);
    }
}
