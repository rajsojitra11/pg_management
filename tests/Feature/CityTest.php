<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Modules\City\Models\City;
use Modules\User\Models\User;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = ['city-list', 'city-create', 'city-edit', 'city-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);

        // Insert dependencies: country and state records (must exist before any factory usage)
        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'India',
            'code' => 'IN',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('states')->insert([
            'id' => 1,
            'name' => 'Maharashtra',
            'code' => 'MH',
            'country_id' => 1,
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
        $response = $this->get(route('city.index'));
        $response->assertStatus(200);
    }

    // Test 2: Can create without remarks
    public function test_can_create_without_remarks(): void
    {
        $data = [
            'name' => 'Mumbai',
            'state_id' => 1,
            'country_id' => 1,
        ];

        $response = $this->postJson(route('city.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('cities', [
            'name' => 'Mumbai',
            'state_id' => 1,
            'country_id' => 1,
            'created_by' => $this->user->id,
        ]);

        $record = City::where('name', $data['name'])->first();
        $this->assertLogRecord('city_logs', 'city_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    // Test 3: Can create with user remarks
    public function test_can_create_with_user_remarks(): void
    {
        $data = [
            'name' => 'Pune',
            'state_id' => 1,
            'country_id' => 1,
            'user_remark' => 'Test remark for creation',
        ];

        $response = $this->postJson(route('city.store'), $data);
        $response->assertStatus(200);

        $record = City::where('name', $data['name'])->first();
        $this->assertLogRecord('city_logs', 'city_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
            'user_remark_contains' => 'Test remark for creation',
        ]);
    }

    // Test 5: Edit returns JSON
    public function test_can_edit_returns_json(): void
    {
        $record = City::factory()->create();

        $response = $this->getJson(route('city.edit', $record->id));
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
        $response->assertJsonStructure(['result']);
    }

    // Test 6: Cannot update without remarks
    public function test_cannot_update_without_remarks(): void
    {
        $record = City::factory()->create();
        $data = [
            'id' => $record->id,
            'name' => 'Updated City Name',
            'state_id' => 1,
            'country_id' => 1,
        ];

        $response = $this->putJson(route('city.update', $record->id), $data);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_remark']);
    }

    // Test 7: Can update with remarks
    public function test_can_update_with_remarks(): void
    {
        $record = City::factory()->create();
        $data = [
            'id' => $record->id,
            'name' => 'Updated City Name',
            'state_id' => 1,
            'country_id' => 1,
            'user_remark' => 'Updating record',
        ];

        $response = $this->putJson(route('city.update', $record->id), $data);
        $response->assertStatus(200);

        $record->refresh();
        $this->assertEquals('Updated City Name', $record->name);
        $this->assertEquals(1, $record->state_id);
        $this->assertEquals($this->user->id, $record->updated_by);

        $this->assertLogRecord('city_logs', 'city_id', $record->id, 'updated', $this->user->id, [
            'user_remark' => 'Updating record',
        ]);
    }

    // Test 8: Update log tracks changes
    public function test_update_log_tracks_changes(): void
    {
        $record = City::factory()->create(['name' => 'Original Name']);
        $data = [
            'id' => $record->id,
            'name' => 'Updated Name',
            'state_id' => 1,
            'country_id' => 1,
            'user_remark' => 'Testing change tracking',
        ];

        $this->putJson(route('city.update', $record->id), $data);

        $this->assertLogRecord('city_logs', 'city_id', $record->id, 'updated', $this->user->id, [
            'user_remark' => 'Testing change tracking',
            'old_value_check' => ['name' => 'Original Name'],
            'new_value_check' => ['name' => 'Updated Name'],
        ]);
    }

    // Test 10: Cannot delete without remarks
    public function test_cannot_delete_without_remarks(): void
    {
        $record = City::factory()->create();

        $response = $this->deleteJson(route('city.destroy', $record->id), [
            'id' => $record->id,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_remark']);
    }

    // Test 11: Can delete with remarks
    public function test_can_delete_with_remarks(): void
    {
        $record = City::factory()->create();

        $response = $this->deleteJson(route('city.destroy', $record->id), [
            'id' => $record->id,
            'user_remark' => 'Removing this record',
        ]);
        $response->assertStatus(200);

        $this->assertSoftDeleted('cities', [
            'id' => $record->id,
            'deleted_by' => $this->user->id,
        ]);

        $this->assertLogRecord('city_logs', 'city_id', $record->id, 'deleted', $this->user->id, [
            'user_remark' => 'Removing this record',
            'expect_null_new_values' => true,
        ]);
    }

    // Test 13: Validation on missing required fields
    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('city.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
