<?php

namespace Tests\Feature\Uye;

use App\Models\EducationLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_uye_bilgileri_sayfasi_dogrulanmis_uye_icin_goruntulenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_sifre_islemleri_sayfasi_dogrulanmis_uye_icin_goruntulenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.password.edit'));

        $response->assertOk();
    }

    public function test_hesap_islemleri_sayfasi_dogrulanmis_uye_icin_goruntulenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.account.edit'));

        $response->assertOk();
    }

    public function test_bildirim_tercihleri_guncellenebilir(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.preferences.update'), [
            'results_email' => '1',
            'marketing_email' => '1',
        ])->assertRedirect(route('profile.account.edit'));

        $this->assertSame([
            'results_email' => true,
            'submission_database' => false,
            'marketing_email' => true,
        ], $user->fresh()->preferences);
    }

    public function test_uye_bilgileri_guncellenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => 'Ahmet',
            'last_name' => 'Demir',
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $this->assertSame('Ahmet', $user->fresh()->first_name);
        $this->assertSame('Demir', $user->fresh()->last_name);
    }

    public function test_ad_soyad_bos_birakilamaz(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => '',
            'last_name' => '',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_egitim_durumu_belirtilmeden_bilgiler_guncellenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertNull($user->fresh()->education_level_id);
    }

    public function test_egitim_durumu_secilebilir(): void
    {
        $user = User::factory()->create();
        $level = EducationLevel::create(['status' => true, 'sort_order' => 10]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'education_level_id' => $level->id,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertSame($level->id, $user->fresh()->education_level_id);
    }

    public function test_gecersiz_egitim_durumu_reddedilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'education_level_id' => (string) Str::uuid(),
        ]);

        $response->assertSessionHasErrors(['education_level_id']);
    }

    public function test_cinsiyet_ve_dogum_tarihi_belirtilmeden_bilgiler_guncellenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertNull($user->fresh()->gender);
        $this->assertNull($user->fresh()->date_of_birth);
    }

    public function test_cinsiyet_ve_dogum_tarihi_guncellenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'gender' => 'female',
            'date_of_birth' => '1990-05-15',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertSame('female', $user->fresh()->gender);
        $this->assertSame('1990-05-15', $user->fresh()->date_of_birth->format('Y-m-d'));
    }

    public function test_gecersiz_cinsiyet_reddedilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'gender' => 'diger',
        ]);

        $response->assertSessionHasErrors(['gender']);
    }

    public function test_gelecek_bir_dogum_tarihi_reddedilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'date_of_birth' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['date_of_birth']);
    }

    public function test_e_posta_degisince_dogrulama_sifirlanir(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'yeni-e-posta@ornek-uye.test',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_dogrulanmamis_uye_bilgileri_sayfasina_erisemez(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_sifre_guncellenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'yeni-guclu-sifre',
            'password_confirmation' => 'yeni-guclu-sifre',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertTrue(Hash::check('yeni-guclu-sifre', $user->fresh()->password));
    }

    public function test_yanlis_mevcut_sifreyle_sifre_guncellenemez(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'yanlis-sifre',
            'password' => 'yeni-guclu-sifre',
            'password_confirmation' => 'yeni-guclu-sifre',
        ]);

        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);
    }

    public function test_uye_hesabini_dogru_sifreyle_silebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->assertModelMissing($user);
    }

    public function test_uye_hesabini_yanlis_sifreyle_silemez(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'yanlis-sifre',
        ]);

        $response->assertSessionHasErrorsIn('userDeletion', ['password']);
        $this->assertModelExists($user);
    }
}
