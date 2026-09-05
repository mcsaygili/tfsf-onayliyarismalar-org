<?php

namespace App\Services;

use App\Models\InstitutionStaff;
use App\Models\Temsilci;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReviewerAccountLock
{
    /** Acquire after the competition mutex; retain until the decision commits. */
    public static function acquire(Model $actor): InstitutionStaff|Temsilci
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Reviewer authority must be checked inside a transaction.');
        }
        abort_unless($actor instanceof InstitutionStaff || $actor instanceof Temsilci, 404);
        $current = $actor->newQuery()->whereKey($actor->id)->lockForUpdate()->first();
        abort_unless($current && $current->status
            && is_string($actor->security_stamp) && is_string($current->security_stamp)
            && hash_equals($current->security_stamp, $actor->security_stamp), 404);

        return $current;
    }
}
