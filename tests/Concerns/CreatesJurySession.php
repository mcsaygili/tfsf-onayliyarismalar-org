<?php

namespace Tests\Concerns;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Services\JurySessionService;
use Spatie\Permission\PermissionRegistrar;

trait CreatesJurySession
{
    use CreatesEvaluationRevision;

    private function sessionFixture(): array
    {
        $f = $this->evaluationFixture();
        $reviewer = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $reviewer->givePermissionTo('institution.competitions.manage');
        $finalRound = $f['competition']->evaluationRounds()->create(['round_number' => 2, 'name' => 'Final', 'method' => 'committee', 'status' => 'open', 'is_final' => true]);
        $decision = $finalRound->committeeDecisions()->create(['submission_photo_id' => $f['photo']->id, 'decision' => 'selected', 'score' => 7, 'rank' => 1]);
        $session = app(JurySessionService::class)->ensureForRound($finalRound);
        $attendance = $session->attendances->sole();
        app(JurySessionService::class)->declare($f['competition'], $f['juror'], ['session_version' => $session->fresh()->version, 'conflict_declared' => false]);
        app(JurySessionService::class)->update($f['competition'], $reviewer, ['session_version' => $session->fresh()->version, 'quorum' => 1, 'action' => 'open', 'attendances' => [$attendance->id => 'present']]);
        $session->refresh();
        $attendance->refresh();
        $this->assertTrue($session->hasQuorum());

        return $f + compact('reviewer', 'finalRound', 'session', 'attendance', 'decision');
    }

    private function sessionPayload(array $f, array $overrides = []): array
    {
        return array_replace(['session_version' => $f['session']->version, 'quorum' => 1, 'action' => 'close', 'minutes' => 'Kurul değerlendirmesini tamamladı.'], $overrides);
    }
}
