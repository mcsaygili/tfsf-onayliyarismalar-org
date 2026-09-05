<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Http\Controllers\Eys\UserController;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unprivileged_user_cannot_list_create_or_edit_other_accounts(): void
    {
        $actor = EysUser::factory()->create();
        $target = EysUser::factory()->create();
        $this->actingAs($actor, 'eys');
        foreach (['eys.users.index', 'eys.users.create'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->get(route('eys.users.edit', $target))->assertForbidden();
        $this->post(route('eys.users.store'), ['email' => 'new@example.test'])->assertForbidden();
        $this->patch(route('eys.users.update', $target), ['email' => 'taken@example.test', 'status' => 0])->assertForbidden();
        $this->assertSame($target->email, $target->fresh()->email);
        $this->assertTrue($target->fresh()->status);
    }

    public function test_own_profile_remains_available_without_user_management_permission(): void
    {
        $actor = EysUser::factory()->create();
        $this->actingAs($actor, 'eys')->get(route('eys.users.edit', $actor))->assertOk()->assertDontSee('name="status"', false);
        $this->patch(route('eys.users.update', $actor), ['email' => $actor->email, 'first_name' => 'Updated'])
            ->assertRedirect(route('eys.users.edit', $actor));
        $this->assertSame('Updated', $actor->fresh()->first_name);
        $this->get(route('eys.users.edit', $actor))->assertOk();
    }

    public function test_own_email_change_requires_current_password_and_rotates_remember_token(): void
    {
        $actor = EysUser::factory()->create();
        $this->actingAs($actor, 'eys');
        $this->patch(route('eys.users.update', $actor), ['email' => 'changed@example.test'])->assertSessionHasErrors('current_password');
        $this->assertSame($actor->email, $actor->fresh()->email);
        $this->patch(route('eys.users.update', $actor), ['email' => 'changed@example.test', 'current_password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertSame('changed@example.test', $actor->fresh()->email);
        $this->assertNotSame($actor->remember_token, $actor->fresh()->remember_token);
        $this->get(route('eys.users.edit', $actor))->assertOk();
    }

    public function test_own_status_is_not_self_service(): void
    {
        $actor = EysUser::factory()->create();
        $this->actingAs($actor, 'eys')->patch(route('eys.users.update', $actor), ['email' => $actor->email, 'status' => 0])->assertForbidden();
        $this->assertTrue($actor->fresh()->status);
    }

    public function test_profile_editor_cannot_take_over_or_disable_another_account(): void
    {
        $actor = $this->actor('edit');
        $target = EysUser::factory()->create();
        $this->actingAs($actor, 'eys');
        $this->patch(route('eys.users.update', $target), ['email' => 'takeover@example.test'])->assertForbidden();
        $this->patch(route('eys.users.update', $target), ['email' => $target->email, 'status' => 0])->assertForbidden();
        $this->patch(route('eys.users.update', $target), ['email' => $target->email, 'first_name' => 'Updated'])->assertSessionHasNoErrors();
        $this->assertSame('Updated', $target->fresh()->first_name);
        $this->assertSame($target->email, $target->fresh()->email);
        $this->assertTrue($target->fresh()->status);
    }

    public function test_viewer_sees_list_without_unauthorized_creation_or_other_account_actions(): void
    {
        $actor = $this->actor('view');
        $other = EysUser::factory()->create();
        $this->actingAs($actor, 'eys')->get(route('eys.users.index'))->assertOk()
            ->assertDontSee(route('eys.users.create'))->assertDontSee(route('eys.users.edit', $other))
            ->assertDontSee(route('eys.users.roles.edit', $other));
    }

    public function test_foreign_module_permission_does_not_authorize_eys_user_management(): void
    {
        $actor = $this->actor('manage', Module::Institution);
        $this->assertTrue($actor->can('eys.users.manage'));
        $this->actingAs($actor, 'eys')->get(route('eys.users.index'))->assertForbidden();
    }

    public function test_profile_editor_cannot_revert_an_email_changed_after_route_binding(): void
    {
        $actor = $this->actor('edit');
        $target = EysUser::factory()->create();
        $this->actingAs($actor, 'eys');
        $target->fresh()->update(['email' => 'new-owner@example.test']);
        $request = Request::create('/', 'PATCH', ['email' => $target->email, 'first_name' => 'Updated']);
        $request->setUserResolver(fn () => $actor);
        try {
            app(UserController::class)->update($request, $target);
            $this->fail('An ordinary editor must not restore the stale email.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('new-owner@example.test', $target->fresh()->email);
    }

    public function test_own_email_change_rechecks_password_after_http_validation(): void
    {
        $actor = EysUser::factory()->create();
        $this->actingAs($actor, 'eys');
        $actor->fresh()->update(['password' => Hash::make('ChangedElsewhere123!')]);
        $request = Request::create('/', 'PATCH', ['email' => 'changed@example.test', 'current_password' => 'password']);
        $request->setUserResolver(fn () => $actor);
        try {
            app(UserController::class)->update($request, $actor);
            $this->fail('The previously valid password must be rechecked under lock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('current_password', $exception->errors());
        }
        $this->assertSame($actor->email, $actor->fresh()->email);
    }

    private function actor(string $action, Module $module = Module::Eys): EysUser
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($module->value);
        Permission::firstOrCreate(['name' => 'eys.users.'.$action, 'guard_name' => 'eys']);
        $actor = EysUser::factory()->create();
        $actor->givePermissionTo('eys.users.'.$action);

        return $actor;
    }
}
