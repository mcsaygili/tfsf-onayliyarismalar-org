<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EducationLevel;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\Temsilci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TemsilciTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Temsilci->value);
        Permission::firstOrCreate(['name' => 'representative.representatives.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('representative.representatives.manage');

        return $user;
    }

    public function test_temsilci_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();
        Temsilci::factory()->count(2)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.temsilciler.index'));

        $response->assertOk();
    }

    public function test_yeni_temsilci_olusturulabilir(): void
    {
        $user = $this->admin();
        $level = EducationLevel::create(['status' => true, 'sort_order' => 10]);
        $level->upsertTranslations(['tr' => ['name' => 'Lisans']]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.temsilciler.store'), [
            'email' => 'temsilci@example.com',
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'phone' => '0555 000 00 00',
            'education_level_id' => $level->id,
            'status' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('eys.temsilciler.index'));

        $temsilci = Temsilci::query()->where('email', 'temsilci@example.com')->firstOrFail();
        $this->assertSame($level->id, $temsilci->education_level_id);
        $this->assertNotNull($temsilci->email_verified_at);
    }

    public function test_temsilci_guncellenebilir_ve_silinebilir(): void
    {
        $user = $this->admin();
        $temsilci = Temsilci::factory()->create();

        $updateResponse = $this->actingAs($user, 'eys')->patch(route('eys.temsilciler.update', $temsilci), [
            'email' => $temsilci->email,
            'first_name' => 'Güncel',
            'last_name' => $temsilci->last_name,
            'status' => '0',
        ]);

        $updateResponse->assertRedirect(route('eys.temsilciler.index'));
        $temsilci->refresh();
        $this->assertSame('Güncel', $temsilci->first_name);
        $this->assertFalse($temsilci->status);

        $deleteResponse = $this->actingAs($user, 'eys')->delete(route('eys.temsilciler.destroy', $temsilci));
        $deleteResponse->assertRedirect(route('eys.temsilciler.index'));
        $this->assertModelMissing($temsilci);
    }

    public function test_izinsiz_kullanici_temsilciler_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.temsilciler.index'));

        $response->assertForbidden();
    }
}
