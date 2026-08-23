<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\AgeEligibilityRule;
use App\Models\CaptureDevice;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompetitionCategoryReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $permission): EysUser
    {
        $user = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'eys']);
        $user->givePermissionTo($permission);

        return $user;
    }

    public function test_dort_referans_listesi_kendi_izinleriyle_goruntulenebilir(): void
    {
        foreach ([
            ['eys.participant_genders.manage', 'eys.participant-genders.index'],
            ['eys.age_eligibility_rules.manage', 'eys.age-eligibility-rules.index'],
            ['eys.member_groups.manage', 'eys.member-groups.index'],
            ['eys.capture_devices.manage', 'eys.capture-devices.index'],
        ] as [$permission, $route]) {
            $this->actingAs($this->admin($permission), 'eys')->get(route($route))->assertOk();
        }
    }

    public function test_fotograf_uretim_cihazi_tr_en_cevirileriyle_olusturulabilir(): void
    {
        $admin = $this->admin('eys.capture_devices.manage');
        $this->actingAs($admin, 'eys')->get(route('eys.capture-devices.create'))->assertOk();
        $response = $this->actingAs($admin, 'eys')->post(route('eys.capture-devices.store'), [
            'code' => 'scanner', 'status' => '1', 'sort_order' => 40,
            'tr' => ['name' => 'Tarayıcı', 'description' => 'Film tarayıcı'],
            'en' => ['name' => 'Scanner', 'description' => 'Film scanner'],
        ]);
        $response->assertRedirect(route('eys.capture-devices.index'));
        $device = CaptureDevice::firstOrFail();
        $this->actingAs($admin, 'eys')->get(route('eys.capture-devices.edit', $device))->assertOk();
        $this->assertSame('Tarayıcı', $device->getTranslation('tr', false)?->name);
        $this->assertSame('Scanner', $device->getTranslation('en', false)?->name);
    }

    public function test_izinsiz_kullanici_referans_veriye_erisemez(): void
    {
        $this->actingAs(EysUser::factory()->create(), 'eys')->get(route('eys.member-groups.index'))->assertForbidden();
    }

    public function test_yas_uygunluk_kurali_sinir_bilgileriyle_olusturulabilir(): void
    {
        $admin = $this->admin('eys.age_eligibility_rules.manage');
        $this->actingAs($admin, 'eys')->get(route('eys.age-eligibility-rules.create'))->assertOk();
        $response = $this->actingAs($admin, 'eys')->post(route('eys.age-eligibility-rules.store'), [
            'code' => 'under-25', 'minimum_age' => 0, 'maximum_age' => 25,
            'minimum_inclusive' => 1, 'maximum_inclusive' => 0, 'status' => 1, 'sort_order' => 60,
            'tr' => ['name' => '25 Yaş Altı', 'description' => 'Sonlanma tarihinde 25 yaş altı.'],
            'en' => ['name' => 'Under 25', 'description' => 'Under 25 on the end date.'],
        ]);

        $response->assertRedirect(route('eys.age-eligibility-rules.index'));
        $rule = AgeEligibilityRule::firstOrFail();
        $this->assertSame(25, $rule->maximum_age);
        $this->assertFalse($rule->maximum_inclusive);
        $this->actingAs($admin, 'eys')->get(route('eys.age-eligibility-rules.edit', $rule))->assertOk();
    }
}
