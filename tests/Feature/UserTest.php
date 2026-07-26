<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected $testRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $permissions = ['users-list', 'users-create', 'users-edit', 'users-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);
        $this->actingAs($this->user);

        // Create a role to assign to new users
        $this->testRole = Role::firstOrCreate(
            ['name' => 'employee', 'guard_name' => 'web'],
            ['title' => 'Employee', 'title_tag' => 'employee']
        );
    }

    protected function validUserData(array $overrides = []): array
    {
        return array_merge([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'mobile' => '9876543210',
            'username' => 'johndoe',
            'password' => 'Password1@',
            'confirm_password' => 'Password1@',
            'status' => 'Active',
            'roles' => [$this->testRole->id],
        ], $overrides);
    }

    // ── Index ────────────────────────────────────────────────────────────

    public function test_can_view_index(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertStatus(200);
    }

    // ── Create ───────────────────────────────────────────────────────────

    public function test_can_create_user(): void
    {
        $response = $this->post(route('users.store'), $this->validUserData());
        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_log_has_exhaustive_fields(): void
    {
        $this->post(route('users.store'), $this->validUserData());

        $newUser = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($newUser);

        $this->assertLogRecord(
            'user_logs',
            'user_id_acting_on',
            $newUser->id,
            'created',
            $this->user->id,
            [
                'system_remark_contains' => 'John',
                'expect_null_old_values' => true,
                'new_values_keys' => ['email', 'name'],
            ]
        );
    }

    public function test_store_creates_user_profile(): void
    {
        $data = $this->validUserData([
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'mobile' => '9876543211',
            'username' => 'janesmith',
            'password' => 'Password1@',
            'confirm_password' => 'Password1@',
        ]);

        $this->post(route('users.store'), $data);

        $newUser = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertDatabaseHas('user_profile', [
            'user_id' => $newUser->id,
        ]);
    }

    // ── Update ───────────────────────────────────────────────────────────

    public function test_can_update_user(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        DB::table('user_profile')->insert([
            'user_id' => $targetUser->id,
            'firstname' => 'Old',
            'lastname' => 'Name',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $targetUser->assignRole($this->testRole);

        $data = [
            'firstname' => 'New',
            'lastname' => 'Name',
            'email' => 'new@example.com',
            'mobile' => $targetUser->mobile,
            'username' => $targetUser->username,
            'status' => 'Active',
            'designation' => 'Test Engineer',
            'roles' => [$this->testRole->id],
        ];

        $response = $this->put(route('users.update', $targetUser->id), $data);
        $response->assertRedirect(route('users.index'));

        $targetUser->refresh();
        $this->assertEquals('new@example.com', $targetUser->email);
    }

    // ── Delete ───────────────────────────────────────────────────────────

    public function test_can_delete_user(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->deleteJson(route('users.destroy', $targetUser->id));
        $response->assertStatus(200);

        $this->assertSoftDeleted('users', [
            'id' => $targetUser->id,
            'deleted_by' => $this->user->id,
        ]);
    }

    public function test_delete_log_has_exhaustive_fields(): void
    {
        $targetUser = User::factory()->create();

        $this->deleteJson(route('users.destroy', $targetUser->id));

        $this->assertLogRecord(
            'user_logs',
            'user_id_acting_on',
            $targetUser->id,
            'deleted',
            $this->user->id,
            [
                'system_remark_contains' => 'deleted',
                'expect_null_new_values' => true,
                'old_values_keys' => ['id', 'email', 'name'],
            ]
        );
    }

    // ── Block/Unblock ────────────────────────────────────────────────────

    public function test_can_block_user(): void
    {
        $targetUser = User::factory()->create(['is_blocked' => false]);

        $response = $this->postJson(route('user-login-status-change'), [
            'id' => $targetUser->id,
            'status' => 1,
        ]);
        $response->assertStatus(200);

        $targetUser->refresh();
        $this->assertEquals(1, $targetUser->is_blocked);
    }

    public function test_block_creates_log_with_correct_activity(): void
    {
        $targetUser = User::factory()->create(['is_blocked' => false]);

        $this->postJson(route('user-login-status-change'), [
            'id' => $targetUser->id,
            'status' => 1,
        ]);

        $this->assertLogRecord(
            'user_logs',
            'user_id_acting_on',
            $targetUser->id,
            'blocked',
            $this->user->id,
            [
                'system_remark_contains' => 'blocked',
            ]
        );
    }

    public function test_unblock_creates_log(): void
    {
        $targetUser = User::factory()->create(['is_blocked' => true]);

        $this->postJson(route('user-login-status-change'), [
            'id' => $targetUser->id,
            'status' => 0,
        ]);

        $targetUser->refresh();
        $this->assertEquals(0, $targetUser->is_blocked);

        $this->assertLogRecord(
            'user_logs',
            'user_id_acting_on',
            $targetUser->id,
            'unblocked',
            $this->user->id,
            [
                'system_remark_contains' => 'unblocked',
            ]
        );
    }

    // ── Activate/Deactivate ──────────────────────────────────────────────

    public function test_can_deactivate_user(): void
    {
        $targetUser = User::factory()->create(['status' => 'active']);

        $response = $this->postJson(route('user-status-change'), [
            'id' => $targetUser->id,
            'status' => 'Inactive',
        ]);
        $response->assertStatus(200);

        $targetUser->refresh();
        $this->assertEquals('Inactive', $targetUser->status);
    }

    public function test_deactivate_creates_log(): void
    {
        $targetUser = User::factory()->create(['status' => 'active']);

        $this->postJson(route('user-status-change'), [
            'id' => $targetUser->id,
            'status' => 'Inactive',
        ]);

        $this->assertLogRecord(
            'user_logs',
            'user_id_acting_on',
            $targetUser->id,
            'deactivated',
            $this->user->id,
            [
                'system_remark_contains' => 'inactive',
            ]
        );
    }

    public function test_activate_creates_log(): void
    {
        $targetUser = User::factory()->create(['status' => 'Inactive']);

        $this->postJson(route('user-status-change'), [
            'id' => $targetUser->id,
            'status' => 'Active',
        ]);

        $targetUser->refresh();
        $this->assertEquals('Active', $targetUser->status);

        $this->assertLogRecord(
            'user_logs',
            'user_id_acting_on',
            $targetUser->id,
            'activated',
            $this->user->id,
            [
                'system_remark_contains' => 'active',
            ]
        );
    }

    // ── Validation ───────────────────────────────────────────────────────

    public function test_validation_missing_required_fields(): void
    {
        $response = $this->post(route('users.store'), []);
        $response->assertSessionHasErrors(['firstname', 'username', 'status']);
    }
}
