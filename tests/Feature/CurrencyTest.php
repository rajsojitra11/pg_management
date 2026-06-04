<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Currency\Models\Currency;
use Modules\User\Models\User;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = ['currency-list', 'currency-create', 'currency-edit', 'currency-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);

        $this->actingAs($this->user);
    }

    // Test 1: Can view index
    public function test_can_view_index(): void
    {
        $response = $this->get(route('currency.index'));
        $response->assertStatus(200);
    }

    // Test 2: Can create without remarks
    public function test_can_create_without_remarks(): void
    {
        $data = [
            'currency_name' => 'US Dollar',
            'currency_symbol' => '$',
        ];

        $response = $this->postJson(route('currency.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('currencies', [
            'currency_name' => 'US Dollar',
            'currency_symbol' => '$',
            'created_by' => $this->user->id,
        ]);

        $record = Currency::where('currency_name', $data['currency_name'])->first();
        $this->assertLogRecord('currency_logs', 'currency_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    // Test 3: Can create with user remarks
    public function test_can_create_with_user_remarks(): void
    {
        $data = [
            'currency_name' => 'Euro',
            'currency_symbol' => "\u{20AC}",
            'user_remark' => 'Test remark for creation',
        ];

        $response = $this->postJson(route('currency.store'), $data);
        $response->assertStatus(200);

        $record = Currency::where('currency_name', $data['currency_name'])->first();
        $this->assertLogRecord('currency_logs', 'currency_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
            'user_remark_contains' => 'Test remark for creation',
        ]);
    }

    // Test 5: Edit returns JSON
    public function test_can_edit_returns_json(): void
    {
        $record = Currency::factory()->create();

        $response = $this->getJson(route('currency.edit', $record->id));
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
        $response->assertJsonStructure(['result']);
    }

    // Test 6: Cannot update without remarks
    public function test_cannot_update_without_remarks(): void
    {
        $record = Currency::factory()->create();
        $data = [
            'currency_name' => 'Updated Currency',
            'currency_symbol' => '!',
        ];

        $response = $this->putJson(route('currency.update', $record->id), $data);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_remark']);
    }

    // Test 7: Can update with remarks
    public function test_can_update_with_remarks(): void
    {
        $record = Currency::factory()->create();
        $data = [
            'currency_name' => 'Updated Currency Name',
            'currency_symbol' => '**',
            'user_remark' => 'Updating record',
        ];

        $response = $this->putJson(route('currency.update', $record->id), $data);
        $response->assertStatus(200);

        $record->refresh();
        $this->assertEquals('Updated Currency Name', $record->currency_name);
        $this->assertEquals('**', $record->currency_symbol);
        $this->assertEquals($this->user->id, $record->updated_by);

        $this->assertLogRecord('currency_logs', 'currency_id', $record->id, 'updated', $this->user->id, [
            'user_remark' => 'Updating record',
        ]);
    }

    // Test 8: Update log tracks changes
    public function test_update_log_tracks_changes(): void
    {
        $record = Currency::factory()->create(['currency_name' => 'Original Name']);
        $data = [
            'currency_name' => 'Updated Name',
            'currency_symbol' => '$',
            'user_remark' => 'Testing change tracking',
        ];

        $this->putJson(route('currency.update', $record->id), $data);

        $this->assertLogRecord('currency_logs', 'currency_id', $record->id, 'updated', $this->user->id, [
            'user_remark' => 'Testing change tracking',
            'old_value_check' => ['currency_name' => 'Original Name'],
            'new_value_check' => ['currency_name' => 'Updated Name'],
        ]);
    }

    // Test 10: Cannot delete without remarks
    public function test_cannot_delete_without_remarks(): void
    {
        $record = Currency::factory()->create();

        $response = $this->deleteJson(route('currency.destroy', $record->id));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_remark']);
    }

    // Test 11: Can delete with remarks
    public function test_can_delete_with_remarks(): void
    {
        $record = Currency::factory()->create();

        $response = $this->deleteJson(route('currency.destroy', $record->id), [
            'user_remark' => 'Removing this record',
        ]);
        $response->assertStatus(200);

        $this->assertSoftDeleted('currencies', [
            'id' => $record->id,
            'deleted_by' => $this->user->id,
        ]);

        $this->assertLogRecord('currency_logs', 'currency_id', $record->id, 'deleted', $this->user->id, [
            'user_remark' => 'Removing this record',
            'expect_null_new_values' => true,
        ]);
    }

    // Test 13: Validation on missing required fields
    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('currency.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency_name']);
    }
}
