<?php

namespace Tests\Feature\Eys;

use App\Models\EysUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_kullanici_listesi_goruntulenebilir(): void
    {
        $user = EysUser::factory()->create();
        EysUser::factory()->count(3)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.users.index'));

        $response->assertOk();
        $response->assertViewHas('users', fn ($users) => $users->total() === 4);
    }

    public function test_kullanici_listesi_sayfalanir(): void
    {
        $user = EysUser::factory()->create();
        EysUser::factory()->count(12)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.users.index'));

        $response->assertViewHas('users', fn ($users) => $users->total() === 13 && $users->lastPage() === 2);
    }

    public function test_yeni_kullanici_eklenebilir(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->post(route('eys.users.store'), [
            'email' => 'yeni@ornek-eys.test',
            'first_name' => 'Ahmet',
            'last_name' => 'Demir',
            'phone' => '+90 555 000 00 00',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('eys.users.index'));

        $newUser = EysUser::where('email', 'yeni@ornek-eys.test')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->status);
    }

    public function test_kullanici_bilgileri_duzenlenebilir(): void
    {
        $user = EysUser::factory()->create();
        $other = EysUser::factory()->create(['first_name' => 'Eski']);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.users.update', $other), [
            'email' => $other->email,
            'first_name' => 'Yeni',
            'last_name' => $other->last_name,
            'status' => 0,
        ]);

        $response->assertRedirect(route('eys.users.index'));

        $this->assertSame('Yeni', $other->fresh()->first_name);
        $this->assertFalse($other->fresh()->status);
    }

    public function test_pasif_kullanici_giris_yapamaz(): void
    {
        $user = EysUser::factory()->create();
        $target = EysUser::factory()->create();

        $this->actingAs($user, 'eys')->patch(route('eys.users.update', $target), [
            'email' => $target->email,
            'status' => 0,
        ]);

        // `actingAs` oturumu test boyunca kalıcı — hedef kullanıcı olarak
        // giriş denemesi yapmadan önce mevcut (aktif) oturumu kapatmamız
        // gerekiyor, aksi halde `guest:eys` login isteğini engeller.
        $this->post(route('eys.logout'));

        $this->post(route('eys.login'), [
            'email' => $target->email,
            'password' => 'password',
        ]);

        $this->assertGuest('eys');
    }

    public function test_giris_yapmamis_kullanici_kullanicilar_sayfasina_erisemez(): void
    {
        $response = $this->get(route('eys.users.index'));

        $response->assertRedirect(route('eys.login'));
    }
}
