<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MemberAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    public static function inactiveStatuses(): array
    {
        return [[0], [90]];
    }

    #[DataProvider('inactiveStatuses')]
    public function test_inactive_member_cannot_log_in(int $status): void
    {
        $user = User::factory()->create(['status' => $status]);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    #[DataProvider('inactiveStatuses')]
    public function test_existing_session_cannot_mutate_after_deactivation(int $status): void
    {
        $user = User::factory()->create(['preferences' => ['results_email' => true]]);
        $this->actingAs($user);
        User::whereKey($user->id)->update(['status' => $status]);

        $this->patchJson(route('profile.preferences.update'), ['results_email' => false])->assertForbidden();
        $this->assertTrue($user->fresh()->preferences['results_email']);
        $this->assertGuest('web');
    }

    public function test_account_restriction_revokes_existing_session_and_redirects_browser(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->restrictions()->create(['type' => 'account', 'reason' => 'Review', 'starts_at' => now()]);
        $this->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_participation_restrictions_do_not_block_profile_access(): void
    {
        $user = User::factory()->create();
        $user->restrictions()->create(['type' => 'participation', 'reason' => 'Review', 'starts_at' => now()]);
        $this->actingAs($user)->get(route('profile.account.edit'))->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_future_and_lifted_restrictions_do_not_block_access(): void
    {
        $user = User::factory()->create();
        foreach ([
            ['starts_at' => now()->subDay(), 'ends_at' => now()],
            ['starts_at' => now()->addDay()],
            ['starts_at' => now()->subDay(), 'lifted_at' => now()],
        ] as $dates) {
            $user->restrictions()->create(['type' => 'account', 'reason' => 'Review', ...$dates]);
        }
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->get(route('profile.account.edit'))->assertOk();
    }
}
