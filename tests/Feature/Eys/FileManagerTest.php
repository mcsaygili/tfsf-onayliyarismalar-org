<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ContextAccess, izinleri model_has_roles → role_has_permissions
     * üzerinden (rol bazlı) sorguluyor — bu yüzden burada, diğer testlerin
     * çoğunun aksine, izinler doğrudan kullanıcıya değil bir role verilip
     * o rol kullanıcıya atanıyor.
     */
    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        $permissions = [];
        foreach (['view', 'create', 'delete', 'manage'] as $action) {
            $permissions[] = Permission::firstOrCreate(['name' => "eys.file_manager.{$action}", 'guard_name' => 'eys']);
        }

        $role = Role::firstOrCreate(['name' => 'file-manager-admin', 'guard_name' => 'eys', 'team_id' => Module::Eys->value]);
        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }

    public function test_yonetici_dosya_yoneticisi_sayfasini_gorebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.filemanager.index'));

        $response->assertOk();
    }

    public function test_izinsiz_kullanici_dosya_yoneticisine_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.filemanager.index'));

        $response->assertForbidden();
    }

    public function test_klasor_icerigi_listelenebilir(): void
    {
        Storage::fake('public');
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->getJson(route('eys.filemanager.browse', ['context' => 'common']));

        $response->assertOk();
        $response->assertJson(['context' => 'common', 'path' => '']);
    }

    public function test_manage_izni_olmayan_kullanici_baska_modulun_kapsamina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.file_manager.view', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.file_manager.view');

        $response = $this->actingAs($user, 'eys')->getJson(route('eys.filemanager.browse', ['context' => 'kurum']));

        $response->assertForbidden();
    }

    public function test_dosya_yolu_traversal_denemesi_reddedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->getJson(route('eys.filemanager.browse', ['context' => 'common', 'path' => '../../etc']));

        $response->assertStatus(422);
    }

    public function test_resim_disi_dosya_sadece_resim_baglaminda_reddedilir(): void
    {
        Storage::fake('public');
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->postJson(route('eys.filemanager.upload'), [
            'context' => 'common',
            'path' => '',
            'filetype' => 'image',
            'files' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
        ]);

        $response->assertOk();
        $response->assertJson(['saved' => 0]);
        $this->assertNotEmpty($response->json('errors'));
    }

    public function test_izin_verilen_dosya_yuklenebilir(): void
    {
        Storage::fake('public');
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->postJson(route('eys.filemanager.upload'), [
            'context' => 'common',
            'path' => '',
            'files' => [UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')],
        ]);

        $response->assertOk();
        $response->assertJson(['saved' => 1]);
    }

    public function test_engellenen_uzanti_reddedilir(): void
    {
        Storage::fake('public');
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->postJson(route('eys.filemanager.upload'), [
            'context' => 'common',
            'path' => '',
            'files' => [UploadedFile::fake()->create('script.php', 10, 'application/x-php')],
        ]);

        $response->assertOk();
        $response->assertJson(['saved' => 0]);
    }

    public function test_bos_yol_ile_silme_istegi_reddedilir(): void
    {
        Storage::fake('public');
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->postJson(route('eys.filemanager.delete'), [
            'context' => 'common',
            'path' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_alt_klasor_silinip_kok_klasor_kalir(): void
    {
        Storage::fake('public');
        $user = $this->admin();
        Storage::disk('public')->makeDirectory('media/gecici');

        $response = $this->actingAs($user, 'eys')->postJson(route('eys.filemanager.bulkDelete'), [
            'context' => 'common',
            'paths' => ['gecici'],
        ]);

        $response->assertOk();
        $response->assertJson(['deleted' => 1]);
        Storage::disk('public')->assertExists('media');
    }
}
