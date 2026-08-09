<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\InstitutionType;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $permission = 'institution.institutions.manage'): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'eys']);
        $user->givePermissionTo($permission);

        return $user;
    }

    public function test_kurum_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();
        Institution::factory()->count(2)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.institutions.index'));

        $response->assertOk();
    }

    public function test_yeni_kurum_olusturulabilir(): void
    {
        $user = $this->admin();
        $type = InstitutionType::create(['status' => true, 'sort_order' => 10]);
        $type->upsertTranslations(['tr' => ['name' => 'Dernek']]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.institutions.store'), [
            'name' => 'Test Derneği',
            'email' => 'test@dernek.org',
            'phone' => '0212 000 00 00',
            'website' => 'https://example.com',
            'address' => 'İstanbul',
            'institution_type_id' => $type->id,
            'status' => '1',
        ]);

        $response->assertRedirect(route('eys.institutions.index'));

        $institution = Institution::query()->firstOrFail();
        $this->assertSame('Test Derneği', $institution->name);
        $this->assertSame($type->id, $institution->institution_type_id);
        $this->assertNotNull($institution->approved_at);
    }

    public function test_kurum_guncellenebilir(): void
    {
        $user = $this->admin();
        $institution = Institution::factory()->create(['name' => 'Eski Ad']);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.institutions.update', $institution), [
            'name' => 'Yeni Ad',
            'email' => $institution->email,
            'phone' => '0000',
            'status' => '0',
        ]);

        $response->assertRedirect(route('eys.institutions.index'));
        $institution->refresh();
        $this->assertSame('Yeni Ad', $institution->name);
        $this->assertFalse($institution->status);
    }

    public function test_yetkilisi_olan_kurum_silinemez(): void
    {
        $user = $this->admin();
        $institution = Institution::factory()->create();
        InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($user, 'eys')->delete(route('eys.institutions.destroy', $institution));

        $response->assertRedirect(route('eys.institutions.index'));
        $this->assertModelExists($institution);
    }

    public function test_yetkilisi_olmayan_kurum_silinebilir(): void
    {
        $user = $this->admin();
        $institution = Institution::factory()->create();

        $response = $this->actingAs($user, 'eys')->delete(route('eys.institutions.destroy', $institution));

        $response->assertRedirect(route('eys.institutions.index'));
        $this->assertModelMissing($institution);
    }

    public function test_izinsiz_kullanici_kurumlar_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.institutions.index'));

        $response->assertForbidden();
    }

    public function test_kurum_yetkilisi_listelenebilir_ve_eklenebilir(): void
    {
        $user = $this->admin('institution.institution_staff.manage');
        $institution = Institution::factory()->create();

        $indexResponse = $this->actingAs($user, 'eys')->get(route('eys.institution-staff.index', $institution));
        $indexResponse->assertOk();

        $storeResponse = $this->actingAs($user, 'eys')->post(route('eys.institution-staff.store', $institution), [
            'email' => 'yetkili@example.com',
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'phone' => '0555 000 00 00',
            'status' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $storeResponse->assertRedirect(route('eys.institution-staff.index', $institution));

        $staff = InstitutionStaff::query()->where('email', 'yetkili@example.com')->firstOrFail();
        $this->assertSame($institution->id, $staff->institution_id);
        $this->assertNotNull($staff->email_verified_at);
    }

    public function test_kurum_yetkilisi_guncellenebilir_ve_silinebilir(): void
    {
        $user = $this->admin('institution.institution_staff.manage');
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $updateResponse = $this->actingAs($user, 'eys')->patch(route('eys.institution-staff.update', [$institution, $staff]), [
            'email' => $staff->email,
            'first_name' => 'Güncel',
            'last_name' => $staff->last_name,
            'status' => '0',
        ]);

        $updateResponse->assertRedirect(route('eys.institution-staff.index', $institution));
        $staff->refresh();
        $this->assertSame('Güncel', $staff->first_name);
        $this->assertFalse($staff->status);

        $deleteResponse = $this->actingAs($user, 'eys')->delete(route('eys.institution-staff.destroy', [$institution, $staff]));
        $deleteResponse->assertRedirect(route('eys.institution-staff.index', $institution));
        $this->assertModelMissing($staff);
    }

    public function test_baska_kuruma_ait_yetkili_duzenlenemez(): void
    {
        $user = $this->admin('institution.institution_staff.manage');
        $institutionA = Institution::factory()->create();
        $institutionB = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institutionB)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.institution-staff.edit', [$institutionA, $staff]));

        $response->assertNotFound();
    }
}
