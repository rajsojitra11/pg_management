<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Tests\TestCase;

class CountryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $permissions = ['country-list', 'country-create', 'country-edit', 'country-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);
        $this->actingAs($this->user);
    }

    public function test_can_view_index(): void
    {
        $response = $this->get(route('country.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_without_remarks(): void
    {
        $data = [
            'name' => 'India',
            'code' => 'IN',
        ];

        $response = $this->postJson(route('country.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('countries', [
            'name' => 'India',
            'code' => 'IN',
            'created_by' => $this->user->id,
        ]);

        $record = Country::where('name', 'India')->first();
        $this->assertLogRecord('country_logs', 'country_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    public function test_can_edit_returns_json(): void
    {
        $record = Country::factory()->create();

        $response = $this->getJson(route('country.edit', $record->id));
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
        $response->assertJsonStructure(['result']);
    }

    public function test_can_update_with_remarks(): void
    {
        $record = Country::factory()->create();
        $data = [
            'name' => 'Updated Country Name',
            'code' => 'UC',
        ];

        $response = $this->putJson(route('country.update', $record->id), $data);
        $response->assertStatus(200);

        $record->refresh();
        $this->assertEquals('Updated Country Name', $record->name);
        $this->assertEquals($this->user->id, $record->updated_by);

        $this->assertLogRecord('country_logs', 'country_id', $record->id, 'updated', $this->user->id, [
        ]);
    }

    public function test_update_log_tracks_changes(): void
    {
        $record = Country::factory()->create(['name' => 'Original Country']);
        $data = [
            'name' => 'Changed Country',
            'code' => 'CC',
        ];

        $this->putJson(route('country.update', $record->id), $data);

        $this->assertLogRecord('country_logs', 'country_id', $record->id, 'updated', $this->user->id, [
            'old_value_check' => ['name' => 'Original Country'],
            'new_value_check' => ['name' => 'Changed Country'],
        ]);
    }

    public function test_can_delete_with_remarks(): void
    {
        $record = Country::factory()->create();

        $response = $this->deleteJson(route('country.destroy', $record->id));
        $response->assertStatus(200);

        $this->assertSoftDeleted('countries', [
            'id' => $record->id,
            'deleted_by' => $this->user->id,
        ]);

        $this->assertLogRecord('country_logs', 'country_id', $record->id, 'deleted', $this->user->id, [
            'expect_null_new_values' => true,
        ]);
    }

    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('country.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
