<?php

namespace Tests;

use App\Services\PanelSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);
        // actingAs skips the real Login event. Emulate its session proof once;
        // requests still pass through the production session middleware.
        $this->withSession([]);
        app(PanelSession::class)->stamp(app('session.store'), $user, $guard ?? config('auth.defaults.guard'));

        return $this;
    }
}
