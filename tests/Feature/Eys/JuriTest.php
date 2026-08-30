<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\Competition;
use App\Models\EducationLevel;
use App\Models\EysUser;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\JuryInvitation;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class JuriTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Juri->value);
        Permission::firstOrCreate(['name' => 'jury.jurors.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('jury.jurors.manage');

        return $user;
    }

    public function test_juri_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();
        Juri::factory()->count(2)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.juriler.index'));

        $response->assertOk();
    }

    public function test_yeni_juri_olusturulabilir(): void
    {
        $user = $this->admin();
        $level = EducationLevel::create(['status' => true, 'sort_order' => 10]);
        $level->upsertTranslations(['tr' => ['name' => 'Lisans']]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.juriler.store'), [
            'email' => 'juri@example.com',
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'phone' => '0555 000 00 00',
            'tckimlikno' => '12345678901',
            'education_level_id' => $level->id,
            'status' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('eys.juriler.index'));

        $juri = Juri::query()->where('email', 'juri@example.com')->firstOrFail();
        $this->assertSame('12345678901', $juri->tckimlikno);
        $this->assertSame($level->id, $juri->education_level_id);
        $this->assertSame('eys', $juri->registration_source);
        $this->assertNotNull($juri->email_verified_at);
    }

    public function test_juri_guncellenebilir_ve_silinebilir(): void
    {
        $user = $this->admin();
        $juri = Juri::factory()->create();

        $updateResponse = $this->actingAs($user, 'eys')->patch(route('eys.juriler.update', $juri), [
            'email' => $juri->email,
            'first_name' => 'Güncel',
            'last_name' => $juri->last_name,
            'status' => '0',
        ]);

        $updateResponse->assertRedirect(route('eys.juriler.index'));
        $juri->refresh();
        $this->assertSame('Güncel', $juri->first_name);
        $this->assertFalse($juri->status);

        $deleteResponse = $this->actingAs($user, 'eys')->delete(route('eys.juriler.destroy', $juri));
        $deleteResponse->assertRedirect(route('eys.juriler.index'));
        $this->assertModelMissing($juri);
    }

    public function test_eys_ayni_epostadaki_bekleyen_daveti_yeni_juriye_baglar(): void
    {
        $user = $this->admin();
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        $competition = Competition::factory()->for($institution)->for($staff)->create();
        $category = $competition->categories()->create(['sort_order' => 10]);
        $invitation = JuryInvitation::create([
            'competition_id' => $competition->id,
            'institution_id' => $institution->id,
            'invited_by' => $staff->id,
            'email' => 'eys-baglanti@example.test',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'locale' => 'tr',
        ]);
        $assignment = $category->jurorAssignments()->create([
            'jury_invitation_id' => $invitation->id,
            'assigned_by' => $staff->id,
            'sort_order' => 10,
        ]);

        $this->actingAs($user, 'eys')->post(route('eys.juriler.store'), [
            'email' => 'eys-baglanti@example.test',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'status' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('eys.juriler.index'));

        $juror = Juri::where('email', 'eys-baglanti@example.test')->firstOrFail();
        $this->assertSame($juror->id, $assignment->fresh()->juror_id);
        $this->assertNull($assignment->fresh()->jury_invitation_id);
        $this->assertSame($juror->id, $invitation->fresh()->accepted_juror_id);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_izinsiz_kullanici_juriler_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.juriler.index'));

        $response->assertForbidden();
    }
}
