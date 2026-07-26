<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Modules\State\Models\State;
use Modules\User\Models\User;
use Tests\TestCase;

class StateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = ['state-list', 'state-create', 'state-edit', 'state-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);

        // Insert dependency: country record (must exist before any factory usage)
        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'India',
            'code' => 'IN',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user);
    }

    // Test 1: Can view index
    public function test_can_view_index(): void
    {
        $response = $this->get(route('state.index'));
        $response->assertStatus(200);
    }

    // Test 2: Can create without remarks
    public function test_can_create_without_remarks(): void
    {
        $data = [
            'name' => 'Maharashtra',
            'code' => 'MH',
            'country_id' => 1,
        ];

        $response = $this->postJson(route('state.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('states', [
            'name' => 'Maharashtra',
            'code' => 'MH',
            'country_id' => 1,
            'created_by' => $this->user->id,
        ]);

        $record = State::where('name', $data['name'])->first();
        $this->assertLogRecord('state_logs', 'state_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    // Test 5: Edit returns JSON
    public function test_can_edit_returns_json(): void
    {
        $record = State::factory()->create();

        $response = $this->getJson(route('state.edit', $record->id));
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
        $response->assertJsonStructure(['result']);
    }

    // Test 7: Can update with remarks
    public function test_can_update_with_remarks(): void
    {
        $record = State::factory()->create();
        $data = [
            'id' => $record->id,
            'name' => 'Updated State Name',
            'code' => 'UN',
            'country_id' => 1,
        ];

        $response = $this->putJson(route('state.update', $record->id), $data);
        $response->assertStatus(200);

        $record->refresh();
        $this->assertEquals('Updated State Name', $record->name);
        $this->assertEquals('UN', $record->code);
        $this->assertEquals($this->user->id, $record->updated_by);

        $this->assertLogRecord('state_logs', 'state_id', $record->id, 'updated', $this->user->id, [
        ]);
    }

    // Test 8: Update log tracks changes
    public function test_update_log_tracks_changes(): void
    {
        $record = State::factory()->create(['name' => 'Original Name']);
        $data = [
            'id' => $record->id,
            'name' => 'Updated Name',
            'code' => 'UN',
            'country_id' => 1,
        ];

        $this->putJson(route('state.update', $record->id), $data);

        $this->assertLogRecord('state_logs', 'state_id', $record->id, 'updated', $this->user->id, [
            'old_value_check' => ['name' => 'Original Name'],
            'new_value_check' => ['name' => 'Updated Name'],
        ]);
    }

    // Test 11: Can delete with remarks
    public function test_can_delete_with_remarks(): void
    {
        $record = State::factory()->create();

        $response = $this->deleteJson(route('state.destroy', $record->id));
        $response->assertStatus(200);

        $this->assertSoftDeleted('states', [
            'id' => $record->id,
            'deleted_by' => $this->user->id,
        ]);

        $this->assertLogRecord('state_logs', 'state_id', $record->id, 'deleted', $this->user->id, [
            'expect_null_new_values' => true,
        ]);
    }

    // Test 13: Validation on missing required fields
    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('state.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
