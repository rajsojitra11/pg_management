<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\MenuMaster\Models\MenuMaster;
use Modules\User\Models\User;
use Tests\TestCase;

class MenuMasterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $permissions = ['menu-master-list', 'menu-master-create', 'menu-master-edit', 'menu-master-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);
        $this->actingAs($this->user);
    }

    public function test_can_view_index(): void
    {
        $response = $this->get(route('menumasters.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_without_remarks(): void
    {
        $data = [
            'menu_title' => 'Test Menu',
            'menu_route' => 'test.route',
        ];

        $response = $this->postJson(route('menumasters.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('menu_masters', [
            'menu_title' => 'Test Menu',
        ]);

        $record = MenuMaster::where('menu_title', 'Test Menu')->first();
        $this->assertLogRecord('menu_master_logs', 'menu_master_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    public function test_can_edit_returns_json(): void
    {
        $record = MenuMaster::factory()->create();

        $response = $this->get(route('menumasters.edit', $record));
        $response->assertStatus(200);
    }

    public function test_can_update_with_remarks(): void
    {
        $record = MenuMaster::factory()->create();
        $data = [
            'menu_title' => 'Updated Menu Title',
        ];

        $response = $this->putJson(route('menumasters.update', $record), $data);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $record->refresh();
        $this->assertEquals('Updated Menu Title', $record->menu_title);

        $this->assertLogRecord('menu_master_logs', 'menu_master_id', $record->id, 'updated', $this->user->id, [
        ]);
    }

    public function test_update_log_tracks_changes(): void
    {
        $record = MenuMaster::factory()->create(['menu_title' => 'Original Title']);
        $data = [
            'menu_title' => 'Changed Title',
        ];

        $this->putJson(route('menumasters.update', $record), $data);

        $this->assertLogRecord('menu_master_logs', 'menu_master_id', $record->id, 'updated', $this->user->id, [
            'old_value_check' => ['menu_title' => 'Original Title'],
            'new_value_check' => ['menu_title' => 'Changed Title'],
        ]);
    }

    public function test_can_delete_with_remarks(): void
    {
        $record = MenuMaster::factory()->create();

        $response = $this->deleteJson(route('menumasters.destroy', $record));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertSoftDeleted('menu_masters', [
            'id' => $record->id,
        ]);

        $this->assertLogRecord('menu_master_logs', 'menu_master_id', $record->id, 'deleted', $this->user->id, [
            'expect_null_new_values' => true,
        ]);
    }

    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('menumasters.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['menu_title']);
    }
}
