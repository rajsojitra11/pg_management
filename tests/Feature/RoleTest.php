<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $permissions = ['role-list', 'role-create', 'role-edit', 'role-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);
        $this->actingAs($this->user);
    }

    // ── Index ────────────────────────────────────────────────────────────

    public function test_can_view_index(): void
    {
        $response = $this->get(route('roles.index'));
        $response->assertStatus(200);
    }

    // ── Create ───────────────────────────────────────────────────────────

    public function test_can_create_role_with_permissions(): void
    {
        $perm1 = Permission::firstOrCreate(['name' => 'test-perm-1', 'guard_name' => 'web'], ['title' => 'Test Perm 1', 'title_tag' => 'test-perm-1']);
        $perm2 = Permission::firstOrCreate(['name' => 'test-perm-2', 'guard_name' => 'web'], ['title' => 'Test Perm 2', 'title_tag' => 'test-perm-2']);

        $data = [
            'name' => 'Manager',
            'permission' => [$perm1->id, $perm2->id],
        ];

        $response = $this->post(route('roles.store'), $data);
        $response->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', [
            'name' => 'Manager',
            'created_by' => $this->user->id,
        ]);

        $role = Role::where('name', 'Manager')->first();
        $this->assertTrue($role->hasPermissionTo('test-perm-1'));
        $this->assertTrue($role->hasPermissionTo('test-perm-2'));
    }

    public function test_create_log_has_exhaustive_fields(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'log-test-perm', 'guard_name' => 'web'], ['title' => 'Log Test', 'title_tag' => 'log-test-perm']);

        $this->post(route('roles.store'), [
            'name' => 'LogTestRole',
            'permission' => [$perm->id],
        ]);

        $role = Role::where('name', 'LogTestRole')->first();
        $this->assertNotNull($role);

        $this->assertLogRecord(
            'role_logs',
            'role_id',
            $role->id,
            'created',
            $this->user->id,
            [
                'system_remark_contains' => 'LogTestRole',
                'expect_null_old_values' => true,
                'new_values_keys' => ['name', 'guard_name'],
            ]
        );
    }

    // ── Update ───────────────────────────────────────────────────────────

    public function test_can_update_role_permissions(): void
    {
        $perm1 = Permission::firstOrCreate(['name' => 'perm-a', 'guard_name' => 'web'], ['title' => 'Perm A', 'title_tag' => 'perm-a']);
        $perm2 = Permission::firstOrCreate(['name' => 'perm-b', 'guard_name' => 'web'], ['title' => 'Perm B', 'title_tag' => 'perm-b']);
        $perm3 = Permission::firstOrCreate(['name' => 'perm-c', 'guard_name' => 'web'], ['title' => 'Perm C', 'title_tag' => 'perm-c']);

        $role = Role::create([
            'name' => 'Editor',
            'title' => 'Editor',
            'guard_name' => 'web',
            'created_by' => $this->user->id,
        ]);
        $role->syncPermissions([$perm1, $perm2]);

        $data = [
            'name' => 'Editor Updated',
            'permission' => [$perm2->id, $perm3->id],
            'user_remark' => 'Updating role permissions',
        ];

        $response = $this->put(route('roles.update', $role->id), $data);
        $response->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertEquals('Editor Updated', $role->name);
        $this->assertFalse($role->hasPermissionTo('perm-a'));
        $this->assertTrue($role->hasPermissionTo('perm-b'));
        $this->assertTrue($role->hasPermissionTo('perm-c'));
    }

    public function test_update_creates_log_with_changes(): void
    {
        $perm1 = Permission::firstOrCreate(['name' => 'upd-perm-1', 'guard_name' => 'web'], ['title' => 'Upd Perm 1', 'title_tag' => 'upd-perm-1']);
        $perm2 = Permission::firstOrCreate(['name' => 'upd-perm-2', 'guard_name' => 'web'], ['title' => 'Upd Perm 2', 'title_tag' => 'upd-perm-2']);

        $role = Role::create([
            'name' => 'OriginalName',
            'title' => 'OriginalName',
            'guard_name' => 'web',
            'created_by' => $this->user->id,
        ]);
        $role->syncPermissions([$perm1]);

        $this->put(route('roles.update', $role->id), [
            'name' => 'UpdatedName',
            'permission' => [$perm2->id],
            'user_remark' => 'Renaming role',
        ]);

        $this->assertLogRecord(
            'role_logs',
            'role_id',
            $role->id,
            'updated',
            $this->user->id,
            [
                'user_remark' => 'Renaming role',
                'old_value_check' => ['name' => 'OriginalName'],
                'new_value_check' => ['name' => 'UpdatedName'],
            ]
        );
    }

    // ── Delete ───────────────────────────────────────────────────────────

    public function test_can_delete_role(): void
    {
        $role = Role::create([
            'name' => 'Deletable',
            'title' => 'Deletable',
            'guard_name' => 'web',
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('roles.destroy', $role->id), [
            'user_remark' => 'Removing role',
        ]);
        $response->assertStatus(200);

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }

    public function test_delete_creates_log(): void
    {
        $role = Role::create([
            'name' => 'ToDelete',
            'title' => 'ToDelete',
            'guard_name' => 'web',
            'created_by' => $this->user->id,
        ]);

        $this->deleteJson(route('roles.destroy', $role->id), [
            'user_remark' => 'Testing delete log',
        ]);

        $this->assertLogRecord(
            'role_logs',
            'role_id',
            $role->id,
            'deleted',
            $this->user->id,
            [
                'system_remark_contains' => 'deleted',
                'expect_null_new_values' => true,
            ]
        );
    }

    public function test_cannot_delete_admin_role(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'title' => 'Admin',
            'guard_name' => 'web',
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('roles.destroy', $role->id), [
            'user_remark' => 'Trying to delete admin',
        ]);
        $response->assertJson(['status_code' => 403]);
    }

    // ── Validation ───────────────────────────────────────────────────────

    public function test_validation_missing_required_fields(): void
    {
        $response = $this->post(route('roles.store'), []);
        $response->assertSessionHasErrors(['name', 'permission']);
    }
}
