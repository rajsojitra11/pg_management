<?php

namespace Modules\Login\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthUserEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_authenticated_user_with_current_permissions(): void
    {
        $user = User::factory()->create();
        $this->createRoleWithPermissions('Pg_Admin', ['mobile-maintenance-create'], $user);

        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.permissions', ['mobile-maintenance-create'])
            ->assertJsonPath('data.roles', ['Pg_Admin']);
    }

    public function test_reflects_permission_changes_without_re_login(): void
    {
        $user = User::factory()->create();
        $this->createRoleWithPermissions('Pg_Admin', ['mobile-maintenance-create'], $user);

        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/user')
            ->assertJsonPath('data.permissions', ['mobile-maintenance-create']);

        $role = Role::where('name', 'Pg_Admin')->first();
        $role->revokePermissionTo('mobile-maintenance-create');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Auth::forgetGuards();

        $this->assertSame(0, $role->permissions()->count(), 'role_has_permissions pivot not cleared');

        $this->withToken($token)
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.permissions', []);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/user')->assertUnauthorized();
    }
}
