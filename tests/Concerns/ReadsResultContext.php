<?php

namespace Tests\Concerns;

use App\Models\Competition;

trait ReadsResultContext
{
    private function resultContextFor(Competition $competition): string
    {
        return $this->get(route('eys.competitions.show', $competition))->assertOk()->viewData('resultContext');
    }
}
