<?php

namespace Tests\Feature\Auth;

use App\Contracts\SmsSender;
use App\Models\User;
use App\Services\AccountSecurityContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmsPasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function challenge(User $user, string $code = '123456', int $minutes = 10): void
    {
        DB::table('sms_password_reset_codes')->insert([
            'user_id' => $user->id, 'security_context' => app(AccountSecurityContext::class)->fingerprint($user),
            'phone_number' => $user->phone_number, 'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($minutes), 'created_at' => now(),
        ]);
    }

    private function payload(User $user, string $code = '123456'): array
    {
        return ['phone_number' => $user->phone_number, 'code' => $code,
            'password' => 'NewSecurePassword!123', 'password_confirmation' => 'NewSecurePassword!123'];
    }

    public function test_code_is_invalidated_after_five_wrong_attempts_even_across_ips(): void
    {
        $user = User::factory()->create(['phone_number' => '5550000011']);
        $this->challenge($user);
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$i])
                ->postJson(route('password.sms.verify'), $this->payload($user, '000000'))
                ->assertUnprocessable()->assertJsonValidationErrors('code');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.100'])
            ->postJson(route('password.sms.verify'), $this->payload($user))
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_success_consumes_code_and_dispatches_reset_once(): void
    {
        Event::fake([PasswordReset::class]);
        $user = User::factory()->create(['phone_number' => '5550000012']);
        $this->challenge($user);
        $this->postJson(route('password.sms.verify'), $this->payload($user))->assertRedirect(route('login'));
        $this->postJson(route('password.sms.verify'), $this->payload($user))->assertUnprocessable();
        $this->assertTrue(Hash::check('NewSecurePassword!123', $user->fresh()->password));
        $this->assertDatabaseCount('sms_password_reset_codes', 0);
        Event::assertDispatchedTimes(PasswordReset::class, 1);
    }

    public function test_latest_expired_code_does_not_allow_fallback_to_older_code(): void
    {
        $user = User::factory()->create(['phone_number' => '5550000013']);
        $this->challenge($user);
        $this->challenge($user, '654321', -1);
        $this->postJson(route('password.sms.verify'), $this->payload($user))->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_resending_replaces_previous_challenge(): void
    {
        $user = User::factory()->create(['phone_number' => '5550000014']);
        $this->challenge($user);
        $oldId = DB::table('sms_password_reset_codes')->value('id');
        $this->mock(SmsSender::class)->shouldReceive('send')->once()->andReturnTrue();
        $this->post(route('password.sms.send'), ['phone_number' => $user->phone_number])->assertRedirect();
        $this->assertDatabaseCount('sms_password_reset_codes', 1);
        $this->assertDatabaseMissing('sms_password_reset_codes', ['id' => $oldId]);
    }

    public function test_duplicate_phone_does_not_reset_an_arbitrary_account(): void
    {
        $one = User::factory()->create(['phone_number' => '5550000015']);
        $two = User::factory()->create(['phone_number' => $one->phone_number]);
        $this->challenge($one);
        $this->postJson(route('password.sms.verify'), $this->payload($one))->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $one->fresh()->password));
        $this->assertTrue(Hash::check('password', $two->fresh()->password));
    }

    public function test_send_response_does_not_disclose_phone_membership(): void
    {
        $this->mock(SmsSender::class)->shouldNotReceive('send');
        $this->post(route('password.sms.send'), ['phone_number' => '5550000099'])
            ->assertRedirect(route('password.sms.request'))->assertSessionHas('status');
        $this->assertDatabaseCount('sms_password_reset_codes', 0);
    }

    public function test_code_still_works_before_attempt_budget_is_exhausted(): void
    {
        $user = User::factory()->create(['phone_number' => '5550000021']);
        $this->challenge($user);
        for ($i = 0; $i < 4; $i++) {
            $this->postJson(route('password.sms.verify'), $this->payload($user, '000000'))->assertUnprocessable();
        }
        $this->postJson(route('password.sms.verify'), $this->payload($user))->assertRedirect(route('login'));
    }

    public function test_expiry_boundary_and_unbound_legacy_code_are_rejected(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['phone_number' => '5550000022']);
        $this->challenge($user, minutes: 0);
        $this->postJson(route('password.sms.verify'), $this->payload($user))->assertUnprocessable();
        DB::table('sms_password_reset_codes')->update(['expires_at' => now()->addMinutes(10), 'user_id' => null]);
        $this->postJson(route('password.sms.verify'), $this->payload($user))->assertUnprocessable();
    }

    public function test_reassigned_phone_cannot_consume_another_users_challenge(): void
    {
        $old = User::factory()->create(['phone_number' => '5550000023']);
        $this->challenge($old);
        $phone = $old->phone_number;
        $old->update(['phone_number' => null]);
        $replacement = User::factory()->create(['phone_number' => $phone]);
        $this->postJson(route('password.sms.verify'), $this->payload($replacement))->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $replacement->fresh()->password));
    }

    public function test_send_budget_is_shared_across_ips_for_the_same_number(): void
    {
        $user = User::factory()->create(['phone_number' => '5550000024']);
        $this->mock(SmsSender::class)->shouldReceive('send')->times(3)->andReturnTrue();
        for ($i = 1; $i <= 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$i])
                ->postJson(route('password.sms.send'), ['phone_number' => $user->phone_number])->assertRedirect();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.100'])
            ->postJson(route('password.sms.send'), ['phone_number' => $user->phone_number])
            ->assertUnprocessable()->assertJsonValidationErrors('phone_number');
    }

    public function test_provider_failure_invalidates_undelivered_challenge(): void
    {
        $user = User::factory()->create(['phone_number' => '5550000025']);
        $this->mock(SmsSender::class)->shouldReceive('send')->once()->andReturnFalse();
        $this->post(route('password.sms.send'), ['phone_number' => $user->phone_number])->assertRedirect();
        $this->assertDatabaseCount('sms_password_reset_codes', 0);
    }
}
