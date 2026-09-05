<?php

namespace App\Models\Concerns;

use App\Models\Institution;
use Illuminate\Support\Str;

trait HasSecurityStamp
{
    public function initializeHasSecurityStamp(): void
    {
        $this->mergeHidden(['security_stamp', 'remember_context']);
    }

    public static function bootHasSecurityStamp(): void
    {
        static::creating(function ($model) {
            $model->security_stamp = Str::random(64);
        });
        static::updating(function ($model) {
            $fields = $model instanceof Institution ? ['status'] : ['password', 'email', 'phone', 'phone_number', 'status', 'institution_id'];
            if ($model->isDirty($fields)) {
                // One SQL update persists both identity/access changes and revocation.
                $model->security_stamp = Str::random(64);
                if (! $model instanceof Institution) {
                    $model->remember_token = null;
                    $model->remember_context = null;
                }
            }
        });
    }
}
