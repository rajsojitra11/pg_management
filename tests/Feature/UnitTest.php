<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Unit\Models\Unit;
use Modules\User\Models\User;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $permissions = ['unit-list', 'unit-create', 'unit-edit', 'unit-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);
        $this->actingAs($this->user);
    }

    public function test_can_view_index(): void
    {
        $response = $this->get(route('unit.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_without_remarks(): void
    {
        $data = [
            'name' => 'Kilogram',
            'unit_value' => 1,
        ];

        $response = $this->postJson(route('unit.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('units', [
            'name' => 'Kilogram',
            'created_by' => $this->user->id,
        ]);

        $record = Unit::where('name', 'Kilogram')->first();
        $this->assertLogRecord('unit_logs', 'unit_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    public function test_can_create_with_user_remarks(): void
    {
        $data = [
            'name' => 'Gram',
            'unit_value' => 0.001,
            'user_remark' => 'Adding gram unit',
        ];

        $response = $this->postJson(route('unit.store'), $data);
        $response->assertStatus(200);

        $record = Unit::where('name', 'Gram')->first();
        $this->assertLogRecord('unit_logs', 'unit_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
            'user_remark_contains' => 'Adding gram unit',
        ]);
    }

    public function test_can_edit_returns_json(): void
    {
        $record = Unit::factory()->create();

        $response = $this->get(route('unit.edit', $record->id));
        $response->assertStatus(200);
    }

    public function test_cannot_update_without_remarks(): void
    {
        $record = Unit::factory()->create();
        $data = [
            'id' => $record->id,
            'name' => 'Updated Unit',
            'unit_value' => 2,
        ];

        $response = $this->putJson(route('unit.update', $record), $data);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_remark']);
    }

    public function test_can_update_with_remarks(): void
    {
        $record = Unit::factory()->create();
        $data = [
            'id' => $record->id,
            'name' => 'Updated Unit Name',
            'unit_value' => 5,
            'user_remark' => 'Updating unit',
            'child_id' => [],
            'segment_value' => [],
        ];

        $response = $this->putJson(route('unit.update', $record), $data);
        $response->assertStatus(200);

        $record->refresh();
        $this->assertEquals('Updated Unit Name', $record->name);
        $this->assertEquals($this->user->id, $record->updated_by);

        $this->assertLogRecord('unit_logs', 'unit_id', $record->id, 'updated', $this->user->id, [
            'user_remark' => 'Updating unit',
        ]);
    }

    public function test_update_log_tracks_changes(): void
    {
        $record = Unit::factory()->create(['name' => 'Original Unit']);
        $data = [
            'id' => $record->id,
            'name' => 'Changed Unit',
            'unit_value' => 10,
            'user_remark' => 'Testing change tracking',
            'child_id' => [],
            'segment_value' => [],
        ];

        $this->putJson(route('unit.update', $record), $data);

        $this->assertLogRecord('unit_logs', 'unit_id', $record->id, 'updated', $this->user->id, [
            'user_remark' => 'Testing change tracking',
            'old_value_check' => ['name' => 'Original Unit'],
            'new_value_check' => ['name' => 'Changed Unit'],
        ]);
    }

    public function test_cannot_delete_without_remarks(): void
    {
        $record = Unit::factory()->create();

        $response = $this->deleteJson(route('unit.destroy', $record->id));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_remark']);
    }

    public function test_can_delete_with_remarks(): void
    {
        $record = Unit::factory()->create();

        $response = $this->deleteJson(route('unit.destroy', $record->id), [
            'user_remark' => 'Removing unit',
        ]);
        $response->assertStatus(200);

        $this->assertSoftDeleted('units', [
            'id' => $record->id,
            'deleted_by' => $this->user->id,
        ]);

        $this->assertLogRecord('unit_logs', 'unit_id', $record->id, 'deleted', $this->user->id, [
            'user_remark' => 'Removing unit',
            'expect_null_new_values' => true,
        ]);
    }

    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('unit.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
