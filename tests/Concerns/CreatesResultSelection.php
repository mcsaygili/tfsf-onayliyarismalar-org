<?php

namespace Tests\Concerns;

use App\Enums\Module;
use App\Models\AwardReference;
use App\Models\EysUser;
use App\Models\Permission;
use App\Services\CompetitionResultService;
use App\Services\JuryEvaluationService;
use App\Services\JuryPhotoRenderer;
use Database\Seeders\AwardReferenceSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

trait CreatesResultSelection
{
    use CreatesEvaluationRevision, ReadsResultContext;

    private function resultFixture(): array
    {
        $this->seed(AwardReferenceSeeder::class);
        $f = $this->evaluationFixture();
        $root = sys_get_temp_dir().'/tfsf-entry-'.Str::uuid();
        config(['filesystems.disks.local.root' => $root.'/local']);
        Storage::forgetDisk('local');
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));
        $f['storageRoot'] = $root;
        $otherPhoto = $f['photo']->replicate();
        $otherPhoto->sha256 = hash('sha256', 'second result photo');
        $otherPhoto->save();
        $safeBytes = app(JuryPhotoRenderer::class)->render(file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')));
        foreach ([$f['photo'], $otherPhoto] as $photo) {
            $photo->update(['jury_path' => 'fixture/'.$photo->id.'.jpg', 'jury_sanitized_at' => now()]);
            Storage::disk('local')->put($photo->jury_path, $safeBytes);
        }
        $service = app(JuryEvaluationService::class);
        $data = $service->evaluationData($f['assignment'], $f['round']);
        $service->save($f['assignment'], $f['round'], [$f['photo']->id => [$f['criterion']->id => 7], $otherPhoto->id => [$f['criterion']->id => 8]], $data['evaluationContext'], true);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $f['round']->refresh();
        $result = $f['round']->results()->where('submission_photo_id', $f['photo']->id)->sole();
        $otherResult = $f['round']->results()->where('submission_photo_id', $otherPhoto->id)->sole();
        $award = $f['submission']->category->awards()->create(['award_reference_id' => AwardReference::where('code', 'first-prize')->sole()->id, 'quantity' => 1]);
        $reviewer = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $reviewer->givePermissionTo('institution.competitions.manage');
        $this->actingAs($reviewer, 'eys');
        $context = $this->resultContextFor($f['competition']);

        return $f + compact('otherPhoto', 'result', 'otherResult', 'award', 'reviewer', 'context');
    }

    private function awardPayload(array $f, ?string $resultId = null): array
    {
        return ['result_context' => $f['context'], 'award_assignments' => [$f['award']->id => [1 => $resultId ?? $f['result']->id]]];
    }
}
