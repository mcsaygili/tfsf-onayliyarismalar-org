<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EducationLevel;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Uye->value);
        Permission::firstOrCreate(['name' => 'member.members.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('member.members.manage');

        return $user;
    }

    public function test_uye_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();
        User::factory()->count(2)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.uyeler.index'));

        $response->assertOk();
    }

    public function test_yeni_uye_olusturulabilir(): void
    {
        $user = $this->admin();
        $level = EducationLevel::create(['status' => true, 'sort_order' => 10]);
        $level->upsertTranslations(['tr' => ['name' => 'Lisans']]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.uyeler.store'), [
            'username' => 'testuye',
            'email' => 'uye@example.com',
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'phone_number' => '0555 000 00 00',
            'education_level_id' => $level->id,
            'uye_turu' => '1',
            'status' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('eys.uyeler.index'));

        $member = User::query()->where('email', 'uye@example.com')->firstOrFail();
        $this->assertSame('testuye', $member->username);
        $this->assertSame(1, $member->uye_turu);
        $this->assertSame($level->id, $member->education_level_id);
        $this->assertNotNull($member->email_verified_at);
    }

    public function test_uye_guncellenebilir_ve_silinebilir(): void
    {
        $user = $this->admin();
        $member = User::factory()->create(['status' => 1, 'uye_turu' => 0]);

        $updateResponse = $this->actingAs($user, 'eys')->patch(route('eys.uyeler.update', $member), [
            'username' => $member->username,
            'email' => $member->email,
            'first_name' => 'Güncel',
            'last_name' => $member->last_name,
            'uye_turu' => '2',
            'status' => '0',
        ]);

        $updateResponse->assertRedirect(route('eys.uyeler.index'));
        $member->refresh();
        $this->assertSame('Güncel', $member->first_name);
        $this->assertSame(0, $member->status);
        $this->assertSame(2, $member->uye_turu);

        $deleteResponse = $this->actingAs($user, 'eys')->delete(route('eys.uyeler.destroy', $member));
        $deleteResponse->assertRedirect(route('eys.uyeler.index'));
        $this->assertModelMissing($member);
    }

    public function test_izinsiz_kullanici_uyeler_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.uyeler.index'));

        $response->assertForbidden();
    }
}
