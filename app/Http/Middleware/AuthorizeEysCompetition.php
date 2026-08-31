<?php

namespace App\Http\Middleware;

use App\Models\Competition;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeEysCompetition
{
    public function handle(Request $request, Closure $next): Response
    {
        $competition = $request->route('competition');
        $competition = $competition instanceof Competition
            ? $competition
            : Competition::query()->findOrFail($competition);
        Gate::forUser(Auth::guard('eys')->user())->authorize('manage', $competition);

        return $next($request);
    }
}
