<?php

namespace Tests\Feature\Auth;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Permission;
use App\Services\PanelSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PanelSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_real_login_creates_a_valid_session(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $this->login($account, $prefix);
        $this->get(route($prefix.'dashboard'))->assertOk();
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_open_session_is_rejected_after_account_deactivation(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $this->login($account, $prefix);
        $account->fresh()->update(['status' => false]);
        $this->getJson(route($prefix.'dashboard'))->assertForbidden();
        $this->assertGuest($broker === 'users' ? 'web' : $broker);
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_password_change_invalidates_a_previously_authenticated_session(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $this->login($account, $prefix);
        $account->fresh()->update(['password' => Hash::make('ChangedElsewhere123!')]);
        $this->get(route($prefix.'dashboard'))->assertRedirect(route($prefix.'login'))
            ->assertSessionHasErrors(['email' => __('auth.session_expired')]);
        $this->assertGuest($broker === 'users' ? 'web' : $broker);
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_legacy_session_without_proof_requires_a_new_login(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $this->login($account, $prefix);
        app('session.store')->forget(app(PanelSession::class)->key($broker === 'users' ? 'web' : $broker));
        $this->get(route($prefix.'dashboard'))->assertRedirect(route($prefix.'login'));
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_valid_remember_cookie_can_start_a_new_authenticated_session(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $guard = $broker === 'users' ? 'web' : $broker;
        Auth::guard($guard)->getProvider()->updateRememberToken($account, Str::random(60));
        $name = Auth::guard($guard)->getRecallerName();
        $cookie = $account->id.'|'.$account->remember_token.'|'.Auth::guard($guard)->hashPasswordForCookie($account->password);
        Auth::forgetGuards();
        $this->withCookie($name, $cookie)->get(route($prefix.'dashboard'))->assertOk();
        $this->assertAuthenticatedAs($account, $guard);
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_old_remember_cookie_cannot_bypass_password_change(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $guard = $broker === 'users' ? 'web' : $broker;
        Auth::guard($guard)->getProvider()->updateRememberToken($account, Str::random(60));
        $name = Auth::guard($guard)->getRecallerName();
        $cookie = $account->id.'|'.$account->remember_token.'|'.Auth::guard($guard)->hashPasswordForCookie($account->password);
        $account->update(['password' => Hash::make('ChangedElsewhere123!')]);
        Auth::forgetGuards();
        $this->withCookie($name, $cookie)->get(route($prefix.'dashboard'))->assertRedirect(route($prefix.'login'));
        $this->assertGuest($guard);
    }

    public function test_disabled_institution_blocks_login_and_existing_staff_session(): void
    {
        $staff = InstitutionStaff::factory()->create();
        $this->login($staff, 'institution.');
        $staff->institution->update(['status' => false]);
        $this->getJson(route('institution.dashboard'))->assertForbidden();
        $this->post(route('institution.login'), ['email' => $staff->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.institution_inactive')]);
        $this->assertGuest('institution');
    }

    public function test_revoked_eys_permission_does_not_survive_in_cached_guard_relationships(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.roles.manage', 'guard_name' => 'eys']);
        $account = EysUser::factory()->create();
        $account->givePermissionTo('eys.roles.manage');
        $this->login($account, 'eys.');
        $this->get(route('eys.roles.index'))->assertOk();
        $account->fresh()->revokePermissionTo('eys.roles.manage');
        $this->get(route('eys.roles.index'))->assertForbidden();
    }

    #[DataProviderExternal(EmailVerificationSecurityTest::class, 'panels')]
    public function test_self_service_password_change_preserves_only_current_session(string $guard, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $this->login($account, $prefix);
        $sessions = app(PanelSession::class);
        $oldProof = app('session.store')->get($sessions->key($guard));
        $this->put(route($prefix.'password.update'), [
            'current_password' => 'password', 'password' => 'NewOwnPassword123!', 'password_confirmation' => 'NewOwnPassword123!',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewOwnPassword123!', $account->fresh()->password));
        $this->assertNotSame($account->remember_token, $account->fresh()->remember_token);
        $this->get(route($prefix.'dashboard'))->assertOk();
        $this->withSession([$sessions->key($guard) => $oldProof])->getJson(route($prefix.'dashboard'))->assertForbidden();
    }

    private function login($account, string $prefix): void
    {
        $this->post(route($prefix.'login'), ['email' => $account->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($account, $prefix === '' ? 'web' : rtrim($prefix, '.'));
    }
}
