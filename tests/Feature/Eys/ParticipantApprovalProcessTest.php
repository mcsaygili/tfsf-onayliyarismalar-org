<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\ParticipantApprovalProcess;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParticipantApprovalProcessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.participant_approval_processes.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.participant_approval_processes.manage');

        return $user;
    }

    public function test_katilimci_onay_sureci_listesi_yarisma_sistemi_altinda_goruntulenebilir(): void
    {
        $response = $this->actingAs($this->admin(), 'eys')->get(route('eys.participant-approval-processes.index'));

        $response->assertOk();
        $response->assertSee(__('eys.nav.section_competition_system'));
        $response->assertSee(__('eys.nav.reference_data'));
        $response->assertSee(__('eys.nav.participant_approval_processes'));
    }

    public function test_katilimci_onay_sureci_tr_ve_en_cevirileriyle_olusturulabilir(): void
    {
        $response = $this->actingAs($this->admin(), 'eys')->post(route('eys.participant-approval-processes.store'), [
            'code' => 'representative',
            'status' => '1',
            'sort_order' => '10',
            'tr' => ['name' => 'Temsilci', 'description' => 'Türkçe açıklama'],
            'en' => ['name' => 'Representative', 'description' => 'English description'],
        ]);

        $response->assertRedirect(route('eys.participant-approval-processes.index'));
        $process = ParticipantApprovalProcess::query()->firstOrFail();
        $this->assertSame('representative', $process->code);
        $this->assertSame('Temsilci', $process->getTranslation('tr')?->name);
        $this->assertSame('Representative', $process->getTranslation('en')?->name);
    }

    public function test_varsayilan_dilde_ad_ve_aciklama_zorunludur(): void
    {
        $response = $this->actingAs($this->admin(), 'eys')->post(route('eys.participant-approval-processes.store'), [
            'code' => 'institution',
            'status' => '1',
            'tr' => ['name' => '', 'description' => ''],
        ]);

        $response->assertSessionHasErrors(['tr.name', 'tr.description']);
    }

    public function test_katilimci_onay_sureci_guncellenebilir_ve_silinebilir(): void
    {
        $process = ParticipantApprovalProcess::factory()->create(['code' => 'old-code']);
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'eys')->patch(route('eys.participant-approval-processes.update', $process), [
            'code' => 'institution',
            'status' => '0',
            'sort_order' => '20',
            'tr' => ['name' => 'Kurum', 'description' => 'Kurum açıklaması'],
            'en' => ['name' => 'Institution', 'description' => 'Institution description'],
        ]);

        $response->assertRedirect(route('eys.participant-approval-processes.index'));
        $process->refresh();
        $this->assertSame('institution', $process->code);
        $this->assertFalse($process->status);
        $this->assertSame('Kurum', $process->getTranslation('tr')?->name);

        $delete = $this->actingAs($admin, 'eys')->delete(route('eys.participant-approval-processes.destroy', $process));
        $delete->assertRedirect(route('eys.participant-approval-processes.index'));
        $this->assertSoftDeleted($process);
    }

    public function test_izinsiz_kullanici_katilimci_onay_sureclerine_erisemez(): void
    {
        $response = $this->actingAs(EysUser::factory()->create(), 'eys')
            ->get(route('eys.participant-approval-processes.index'));

        $response->assertForbidden();
    }
}
