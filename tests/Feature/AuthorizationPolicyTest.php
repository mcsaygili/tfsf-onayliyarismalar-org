<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Models\Competition;
use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Permission;
use App\Models\Temsilci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_kurum_yalnizca_kendi_yarismasini_guncelleyebilir(): void
    {
        $competition = Competition::factory()->create();
        $otherStaff = InstitutionStaff::factory()->create();

        $this->assertTrue(Gate::forUser($competition->institutionStaff)->allows('update', $competition));
        $this->assertFalse(Gate::forUser($otherStaff)->allows('update', $competition));
    }

    public function test_temsilci_yalnizca_atandigi_yarismada_operasyon_yapabilir(): void
    {
        $assigned = Temsilci::factory()->create();
        $other = Temsilci::factory()->create();
        $competition = Competition::factory()->create(['representative_id' => $assigned->id]);

        $this->assertTrue(Gate::forUser($assigned)->allows('operate', $competition));
        $this->assertFalse(Gate::forUser($other)->allows('operate', $competition));
    }

    public function test_juri_yalnizca_atandigi_kategoriyi_degerlendirebilir(): void
    {
        $competition = Competition::factory()->create();
        $category = $competition->categories()->create(['sort_order' => 10]);
        $assigned = Juri::factory()->create();
        $other = Juri::factory()->create();
        $category->jurorAssignments()->create(['juror_id' => $assigned->id, 'sort_order' => 10]);

        $this->assertTrue(Gate::forUser($assigned)->allows('evaluate', $category));
        $this->assertFalse(Gate::forUser($other)->allows('evaluate', $category));
    }

    public function test_eys_yarisma_yonetimi_policy_ve_modul_izni_birlikte_gerektirir(): void
    {
        $allowed = EysUser::factory()->create();
        $denied = EysUser::factory()->create();
        $competition = Competition::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $allowed->givePermissionTo('institution.competitions.manage');

        $this->assertTrue(Gate::forUser($allowed)->allows('manage', $competition));
        $this->assertFalse(Gate::forUser($denied)->allows('manage', $competition));
    }
}
