<?php

namespace App\Http\Middleware;

use App\Models\Competition;
use App\Services\CompetitionMutationLock;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SerializeCompetitionMutation
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }
        $competition = $request->route('competition');
        if (! $competition instanceof Competition) {
            throw new \LogicException('A bound competition is required.');
        }

        DB::beginTransaction();
        try {
            $request->route()->setParameter('competition', CompetitionMutationLock::acquire($competition->id));
            $response = $next($request);
            // Routing\Pipeline can turn an exception into a response before it
            // reaches us. Such a response must never commit controller writes.
            $failed = isset($response->exception) || $response->getStatusCode() >= 400
                || ($request->hasSession() && in_array('errors', $request->session()->get('_flash.new', []), true)
                    && ! $request->attributes->get('competition_transition_succeeded', false));
            $failed ? DB::rollBack() : DB::commit();

            return $response;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
