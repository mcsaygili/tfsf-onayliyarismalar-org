<?php

namespace Tests\Feature\Auth;

use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Temsilci;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmailPasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    public static function panels(): array
    {
        return [
            'member' => ['users', '', User::class],
            'institution' => ['institution', 'institution.', InstitutionStaff::class],
            'representative' => ['temsilci', 'temsilci.', Temsilci::class],
            'jury' => ['juri', 'juri.', Juri::class],
            'management' => ['eys', 'eys.', EysUser::class],
        ];
    }

    #[DataProvider('panels')]
    public function test_known_unknown_and_throttled_addresses_receive_the_same_response(string $broker, string $prefix, string $model): void
    {
        Notification::fake();
        $user = $model::factory()->create();
        foreach ([$user->email, 'missing@example.test', $user->email] as $email) {
            $this->from(route($prefix.'password.request'))->post(route($prefix.'password.email'), ['email' => $email])
                ->assertRedirect(route($prefix.'password.request'))->assertSessionHasNoErrors()
                ->assertSessionHas('status', __('passwords.requested'));
        }
        Notification::assertCount(1);
        $this->assertDatabaseHas(config('auth.passwords.'.$broker.'.table'), ['email' => $user->email, 'user_id' => $user->id]);
    }

    #[DataProvider('panels')]
    public function test_valid_token_changes_only_its_account_and_is_consumed(string $broker, string $prefix, string $model): void
    {
        $user = $model::factory()->create();
        $other = $model::factory()->create();
        $token = Password::broker($broker)->createToken($user);
        $payload = $this->payload($user->email, $token);
        $this->post(route($prefix.'password.store'), $payload)->assertRedirect(route($prefix.'login'));
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertTrue(Hash::check('password', $other->fresh()->password));
        $this->assertNotSame($user->remember_token, $user->fresh()->remember_token);
        $this->assertDatabaseCount(config('auth.passwords.'.$broker.'.table'), 0);
        $this->post(route($prefix.'password.store'), $payload)->assertSessionHasErrors('email');
    }

    #[DataProvider('panels')]
    public function test_token_does_not_follow_reassigned_email(string $broker, string $prefix, string $model): void
    {
        $user = $model::factory()->create(['email' => 'reassigned@example.test']);
        $token = Password::broker($broker)->createToken($user);
        $user->update(['email' => 'changed@example.test']);
        $replacement = $model::factory()->create(['email' => 'reassigned@example.test']);
        $this->post(route($prefix.'password.store'), $this->payload($replacement->email, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $replacement->fresh()->password));
    }

    #[DataProvider('panels')]
    public function test_expiry_boundary_unbound_and_replaced_tokens_are_rejected(string $broker, string $prefix, string $model): void
    {
        $this->freezeTime();
        $user = $model::factory()->create();
        $passwords = Password::broker($broker);
        $first = $passwords->createToken($user);
        $replacement = $passwords->createToken($user);
        $this->assertFalse($passwords->tokenExists($user, $first));
        $this->assertTrue($passwords->tokenExists($user, $replacement));
        $this->travel(config('auth.passwords.'.$broker.'.expire'))->minutes();
        $this->post(route($prefix.'password.store'), $this->payload($user->email, $replacement))->assertSessionHasErrors('email');
        $token = $passwords->createToken($user);
        DB::table(config('auth.passwords.'.$broker.'.table'))->where('email', $user->email)->update(['user_id' => null]);
        $this->post(route($prefix.'password.store'), $this->payload($user->email, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    #[DataProvider('panels')]
    public function test_send_budget_is_shared_across_ips(string $broker, string $prefix, string $model): void
    {
        Notification::fake();
        $user = $model::factory()->create();
        for ($i = 1; $i <= 4; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$i])
                ->post(route($prefix.'password.email'), ['email' => $user->email])
                ->assertSessionHasNoErrors()->assertSessionHas('status', __('passwords.requested'));
            $this->travel(61)->seconds();
        }
        Notification::assertCount(3);
    }

    public function test_a_member_token_cannot_reset_the_same_email_in_another_panel(): void
    {
        $member = User::factory()->create(['email' => 'shared@example.test']);
        $manager = EysUser::factory()->create(['email' => $member->email]);
        $token = Password::broker('users')->createToken($member);
        $this->post(route('eys.password.store'), $this->payload($manager->email, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $manager->fresh()->password));
    }

    #[DataProvider('panels')]
    public function test_invalid_token_and_unknown_account_have_the_same_error(string $broker, string $prefix, string $model): void
    {
        $account = $model::factory()->create();
        foreach ([$account->email, 'absent@example.test'] as $email) {
            $this->post(route($prefix.'password.store'), $this->payload($email, 'invalid-token'))
                ->assertSessionHasErrors(['email' => __('passwords.token')]);
        }
    }

    public function test_provider_failure_uses_generic_response_and_does_not_log_provider_details(): void
    {
        $user = User::factory()->create();
        Log::spy();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('PRIVATE_PROVIDER_RESPONSE'));
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()->assertSessionHas('status', __('passwords.requested'));
        Log::shouldHaveReceived('warning')->once()->with('Password reset link delivery failed.', [
            'broker' => 'users', 'exception_type' => \RuntimeException::class,
        ]);
    }

    public function test_a_failure_during_reset_rolls_back_password_and_keeps_token(): void
    {
        $user = User::factory()->create();
        $broker = Password::broker('users');
        $token = $broker->createToken($user);
        try {
            $broker->reset($this->payload($user->email, $token), function ($account) {
                $account->update(['password' => Hash::make('MustRollBack123!')]);
                throw new \RuntimeException('Simulated database workflow failure');
            });
            $this->fail('Expected failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated database workflow failure', $exception->getMessage());
        }
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertTrue($broker->tokenExists($user, $token));
    }

    private function payload(string $email, string $token): array
    {
        return ['email' => $email, 'token' => $token, 'password' => 'NewSecurePassword!123', 'password_confirmation' => 'NewSecurePassword!123'];
    }
}
