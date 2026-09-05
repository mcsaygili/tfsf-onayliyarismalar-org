<?php

namespace Tests\Feature\Auth;

use App\Contracts\SmsSender;
use App\Models\InstitutionStaff;
use App\Models\User;
use App\Services\AccountSecurityContext;
use App\Services\PanelSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\TestCase;

class AccountSecurityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_reactivation_does_not_revive_idle_sessions_or_reset_tokens(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $token = Password::broker($broker)->createToken($account);
        $this->actingAs($account, $this->guard($broker));
        $account->fresh()->update(['status' => false]);
        $account->fresh()->update(['status' => true]);
        $this->getJson(route($prefix.'dashboard'))->assertForbidden();
        $this->assertInvalidReset($broker, $account->fresh(), $token);
        $this->post(route($prefix.'login'), ['email' => $account->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->get(route($prefix.'dashboard'))->assertOk();
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_returning_to_original_email_does_not_revive_pending_reset(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $email = $account->email;
        $token = Password::broker($broker)->createToken($account);
        $account->update(['email' => 'changed-'.Str::uuid().'@example.test']);
        $account->update(['email' => $email]);
        $this->assertInvalidReset($broker, $account->fresh(), $token);
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_password_change_invalidates_old_reset_but_allows_fresh_request(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $token = Password::broker($broker)->createToken($account);
        $account->update(['password' => Hash::make('ChangedPassword123!')]);
        $this->assertInvalidReset($broker, $account->fresh(), $token);
        $this->assertFalse(Password::broker($broker)->getRepository()->recentlyCreatedToken($account->fresh()));
        $freshToken = Password::broker($broker)->createToken($account->fresh());
        $this->post(route($prefix.'password.store'), $this->emailPayload($account, $freshToken))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('RecoveredPassword123!', $account->fresh()->password));
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_profile_only_edit_preserves_current_session_and_recovery(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $token = Password::broker($broker)->createToken($account);
        $this->actingAs($account, $this->guard($broker));
        $stamp = $account->security_stamp;
        $account->update(['first_name' => 'New Profile Name']);
        $this->assertSame($stamp, $account->fresh()->security_stamp);
        $this->get(route($prefix.'dashboard'))->assertOk();
        $this->assertTrue(Password::broker($broker)->tokenExists($account->fresh(), $token));
        $this->assertArrayNotHasKey('security_stamp', $account->toArray());
        $this->assertArrayNotHasKey('remember_context', $account->toArray());
    }

    #[DataProviderExternal(EmailPasswordResetSecurityTest::class, 'panels')]
    public function test_old_remember_cookie_is_rejected_and_new_login_can_remember_again(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        $guard = $this->guard($broker);
        $provider = Auth::guard($guard)->getProvider();
        $provider->updateRememberToken($account, Str::random(60));
        $name = Auth::guard($guard)->getRecallerName();
        $cookie = $account->id.'|'.$account->remember_token.'|'.Auth::guard($guard)->hashPasswordForCookie($account->password);
        $account->update(['status' => false]);
        $account->update(['status' => true]);
        Auth::forgetGuards();
        $this->withCookie($name, $cookie)->get(route($prefix.'dashboard'))->assertRedirect(route($prefix.'login'));
        $this->post(route($prefix.'login'), ['email' => $account->email, 'password' => 'password', 'remember' => true])->assertSessionHasNoErrors();
        $fresh = $account->fresh();
        $this->assertNotNull($provider->retrieveByToken($fresh->id, $fresh->remember_token));
        $this->assertTrue(app(AccountSecurityContext::class)->matches($fresh, $fresh->remember_context));
    }

    public function test_institution_reactivation_invalidates_staff_session_recovery_and_remember_cookie(): void
    {
        $account = InstitutionStaff::factory()->create();
        $token = Password::broker('institution')->createToken($account);
        $provider = Auth::guard('institution')->getProvider();
        $provider->updateRememberToken($account, Str::random(60));
        $remember = $account->remember_token;
        $this->actingAs($account, 'institution');
        $account->institution->update(['status' => false]);
        $account->institution->update(['status' => true]);
        $this->getJson(route('institution.dashboard'))->assertForbidden();
        $this->assertInvalidReset('institution', $account->fresh(), $token);
        $this->assertNull($provider->retrieveByToken($account->id, $remember));
        $this->post(route('institution.login'), ['email' => $account->email, 'password' => 'password', 'remember' => true])->assertSessionHasNoErrors();
        $fresh = $account->fresh();
        $this->assertNotSame($remember, $fresh->remember_token);
        $this->assertNotNull($provider->retrieveByToken($fresh->id, $fresh->remember_token));
    }

    public function test_sms_reset_invalidates_previously_issued_email_token(): void
    {
        $user = User::factory()->create(['phone_number' => '5559000011']);
        $emailToken = Password::broker('users')->createToken($user);
        $this->smsChallenge($user);
        $this->postJson(route('password.sms.verify'), $this->smsPayload($user))->assertRedirect(route('login'));
        $this->assertInvalidReset('users', $user->fresh(), $emailToken);
    }

    public function test_email_reset_invalidates_previously_issued_sms_code(): void
    {
        $user = User::factory()->create(['phone_number' => '5559000012']);
        $this->smsChallenge($user);
        $emailToken = Password::broker('users')->createToken($user);
        $this->post(route('password.store'), $this->emailPayload($user, $emailToken))->assertSessionHasNoErrors();
        $this->postJson(route('password.sms.verify'), $this->smsPayload($user))->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertTrue(Hash::check('RecoveredPassword123!', $user->fresh()->password));
    }

    public function test_phone_round_trip_does_not_revive_old_sms_code(): void
    {
        $user = User::factory()->create(['phone_number' => '5559000013']);
        $this->smsChallenge($user);
        $user->update(['phone_number' => '5559000099']);
        $user->update(['phone_number' => '5559000013']);
        $this->postJson(route('password.sms.verify'), $this->smsPayload($user))->assertUnprocessable();
    }

    public function test_naturally_expired_scheduled_account_restriction_does_not_restore_idle_credentials(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $user->restrictions()->create(['type' => 'account', 'reason' => 'Scheduled account restriction', 'starts_at' => now()->addMinutes(2), 'ends_at' => now()->addMinutes(4)]);
        $user->refresh();
        $token = Password::broker('users')->createToken($user);
        $provider = Auth::guard('web')->getProvider();
        $provider->updateRememberToken($user, Str::random(60));
        $remember = $user->remember_token;
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertOk();
        $this->travel(5)->minutes();
        $this->getJson(route('dashboard'))->assertForbidden();
        $this->assertInvalidReset('users', $user->fresh(), $token);
        $this->assertNull($provider->retrieveByToken($user->id, $remember));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_lifting_or_deleting_account_restriction_does_not_restore_prior_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $old = app('session.store')->get(app(PanelSession::class)->key('web'));
        $restriction = $user->restrictions()->create(['type' => 'account', 'reason' => 'Account restriction', 'starts_at' => now()->subMinute()]);
        $restriction->update(['lifted_at' => now()]);
        $restriction->delete();
        $this->assertFalse(app(PanelSession::class)->matches(app('session.store'), $user->fresh(), 'web'));
        $this->withSession([app(PanelSession::class)->key('web') => $old])->getJson(route('dashboard'))->assertForbidden();
    }

    public function test_competition_only_restriction_does_not_revoke_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $restriction = $user->restrictions()->create(['type' => 'competition', 'reason' => 'Participation only', 'starts_at' => now()->subMinute()]);
        $restriction->update(['lifted_at' => now()]);
        $restriction->delete();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_rolled_back_restriction_does_not_revoke_valid_credentials(): void
    {
        $user = User::factory()->create();
        $fingerprint = app(AccountSecurityContext::class)->fingerprint($user);
        DB::beginTransaction();
        $user->restrictions()->create(['type' => 'account', 'reason' => 'Rolled back change', 'starts_at' => now()]);
        DB::rollBack();
        $this->assertDatabaseCount('member_restrictions', 0);
        $this->assertSame($fingerprint, app(AccountSecurityContext::class)->fingerprint($user->fresh()));
    }

    public function test_new_sms_delivery_creates_a_usable_bound_challenge(): void
    {
        $user = User::factory()->create(['phone_number' => '5559000088']);
        $code = null;
        $this->mock(SmsSender::class)->shouldReceive('send')->once()->andReturnUsing(function ($phone, $message) use ($user, &$code) {
            $this->assertSame($user->phone_number, $phone);
            preg_match('/[0-9]{6}/', $message, $matches);
            $code = $matches[0];

            return true;
        });
        $this->post(route('password.sms.send'), ['phone_number' => $user->phone_number])->assertSessionHasNoErrors();
        $this->assertTrue(app(AccountSecurityContext::class)->matches($user, DB::table('sms_password_reset_codes')->value('security_context')));
        $this->postJson(route('password.sms.verify'), array_replace($this->smsPayload($user), ['code' => $code]))->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('SmsPassword123!', $user->fresh()->password));
    }

    public function test_legacy_challenges_without_security_context_are_rejected_even_with_matching_account_id(): void
    {
        $user = User::factory()->create(['phone_number' => '5559000089']);
        $token = Password::broker('users')->createToken($user);
        DB::table('password_reset_tokens')->where('email', $user->email)->update(['security_context' => null]);
        $this->assertInvalidReset('users', $user, $token);
        $this->smsChallenge($user);
        DB::table('sms_password_reset_codes')->where('user_id', $user->id)->update(['security_context' => null]);
        $this->postJson(route('password.sms.verify'), $this->smsPayload($user))->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    private function assertInvalidReset(string $broker, $account, string $token): void
    {
        $this->assertSame(Password::INVALID_TOKEN, Password::broker($broker)->reset($this->emailPayload($account, $token), function () {
            $this->fail('A revoked token must not reach the password-changing callback.');
        }));
    }

    private function emailPayload($account, string $token): array
    {
        return ['email' => $account->email, 'token' => $token, 'password' => 'RecoveredPassword123!', 'password_confirmation' => 'RecoveredPassword123!'];
    }

    private function smsChallenge(User $user): void
    {
        DB::table('sms_password_reset_codes')->insert(['user_id' => $user->id, 'phone_number' => $user->phone_number,
            'security_context' => app(AccountSecurityContext::class)->fingerprint($user), 'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10), 'created_at' => now()]);
    }

    private function smsPayload(User $user): array
    {
        return ['phone_number' => $user->phone_number, 'code' => '123456', 'password' => 'SmsPassword123!', 'password_confirmation' => 'SmsPassword123!'];
    }

    private function guard(string $broker): string
    {
        return $broker === 'users' ? 'web' : $broker;
    }
}
