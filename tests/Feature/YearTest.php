<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\User\Models\User;
use Modules\Year\Models\Year;
use Tests\TestCase;

class YearTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = ['year-list', 'year-create', 'year-edit', 'year-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);

        $this->actingAs($this->user);
    }

    // Test 1: Can view index
    public function test_can_view_index(): void
    {
        $response = $this->get(route('year.index'));
        $response->assertStatus(200);
    }

    // Test 2: Can create without remarks
    public function test_can_create_without_remarks(): void
    {
        $data = [
            'name' => '2050-51',
            'set_default' => false,
        ];

        $response = $this->postJson(route('year.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('years', [
            'name' => '2050-51',
            'created_by' => $this->user->id,
        ]);

        $record = Year::where('name', $data['name'])->first();
        $this->assertLogRecord('year_logs', 'year_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    // Test 5: Edit returns JSON
    public function test_can_edit_returns_json(): void
    {
        $record = Year::factory()->create();

        $response = $this->getJson(route('year.edit', $record->id));
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
        $response->assertJsonStructure(['result']);
    }

    // Test 7: Can update with remarks
    public function test_can_update_with_remarks(): void
    {
        $record = Year::factory()->create();
        $data = [
            'name' => '2051-52',
            'set_default' => false,
        ];

        $response = $this->putJson(route('year.update', $record->id), $data);
        $response->assertStatus(200);

        $record->refresh();
        $this->assertEquals('2051-52', $record->name);
        $this->assertEquals($this->user->id, $record->updated_by);

        $this->assertLogRecord('year_logs', 'year_id', $record->id, 'updated', $this->user->id, [
        ]);
    }

    // Test 8: Update log tracks changes
    public function test_update_log_tracks_changes(): void
    {
        $record = Year::factory()->create(['name' => '2050-51']);
        $data = [
            'name' => '2051-52',
            'set_default' => false,
        ];

        $this->putJson(route('year.update', $record->id), $data);

        $this->assertLogRecord('year_logs', 'year_id', $record->id, 'updated', $this->user->id, [
            'old_value_check' => ['name' => '2050-51'],
            'new_value_check' => ['name' => '2051-52'],
        ]);
    }

    // Test 11: Can delete with remarks
    public function test_can_delete_with_remarks(): void
    {
        $record = Year::factory()->create();

        $response = $this->deleteJson(route('year.destroy', $record->id));
        $response->assertStatus(200);

        $this->assertSoftDeleted('years', [
            'id' => $record->id,
            'deleted_by' => $this->user->id,
        ]);

        $this->assertLogRecord('year_logs', 'year_id', $record->id, 'deleted', $this->user->id, [
            'expect_null_new_values' => true,
        ]);
    }

    // Test 13: Validation on missing required fields
    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('year.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
