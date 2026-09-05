<?php

namespace App\Services;

use App\Models\Competition;
use Illuminate\Support\Facades\DB;

class CompetitionMutationLock
{
    public static function acquire(string $competitionId): Competition
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Competition mutation locks require a transaction.');
        }

        return Competition::whereKey($competitionId)->lockForUpdate()->firstOrFail();
    }
}
