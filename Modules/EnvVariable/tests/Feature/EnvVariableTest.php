<?php

namespace Modules\EnvVariable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\EnvVariable\Models\EnvVariable;
use Modules\User\Models\User;
use Tests\TestCase;

class EnvVariableTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Routes require role:Super_Admin middleware AND permission middleware in the controller
        $permissions = [
            'env-variable-list',
            'env-variable-create',
            'env-variable-edit',
            'env-variable-delete',
        ];
        $this->createRoleWithPermissions('Super_Admin', $permissions, $this->user);

        $this->actingAs($this->user);
    }

    public function test_can_view_env_variables_index(): void
    {
        // Force is_encrypted false to avoid DecryptException on plain-text values
        EnvVariable::factory()->count(3)->create(['is_encrypted' => false]);

        $response = $this->get(route('env-variable.index'));

        $response->assertStatus(200);
        $response->assertViewIs('envvariable::index');
        $response->assertViewHas('envVariables');
    }

    public function test_can_view_create_env_variable_form(): void
    {
        $response = $this->get(route('env-variable.create'));

        $response->assertStatus(200);
        $response->assertViewIs('envvariable::create');
    }

    public function test_can_create_env_variable_without_remarks(): void
    {
        $data = [
            'key' => 'TEST_NEW_VAR',
            'value' => 'test_value',
            'type' => 'text',
            'description' => 'Test variable',
            'is_active' => true,
            'is_encrypted' => false,
        ];

        $response = $this->postJson(route('env-variable.store'), $data);

        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('env_variables', [
            'key' => 'TEST_NEW_VAR',
            'value' => 'test_value',
            'description' => 'Test variable',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $envVariable = EnvVariable::where('key', 'TEST_NEW_VAR')->first();
        $this->assertLogRecord('env_variable_logs', 'env_variable_id', $envVariable->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    public function test_can_view_env_variable(): void
    {
        $envVariable = EnvVariable::factory()->create(['is_encrypted' => false]);

        $response = $this->get(route('env-variable.show', $envVariable));

        $response->assertStatus(200);
        $response->assertViewIs('envvariable::show');
        $response->assertViewHas('envVariable');
    }

    public function test_can_view_edit_env_variable_form(): void
    {
        $envVariable = EnvVariable::factory()->create(['is_encrypted' => false]);

        $response = $this->get(route('env-variable.edit', $envVariable));

        $response->assertStatus(200);
        $response->assertViewIs('envvariable::edit');
        $response->assertViewHas('envVariable');
    }

    public function test_can_update_env_variable(): void
    {
        $envVariable = EnvVariable::factory()->create([
            'value' => 'original_value',
            'description' => 'Original description',
            'is_encrypted' => false,
        ]);

        $data = [
            'key' => $envVariable->key,
            'value' => 'updated_value',
            'type' => 'text',
            'description' => 'Updated description',
            'is_active' => true,
            'is_encrypted' => false,
        ];

        $response = $this->putJson(route('env-variable.update', $envVariable), $data);

        $response->assertStatus(200);

        $envVariable->refresh();
        $this->assertEquals('updated_value', $envVariable->value);
        $this->assertEquals('Updated description', $envVariable->description);
        $this->assertEquals($this->user->id, $envVariable->updated_by);

        $this->assertLogRecord('env_variable_logs', 'env_variable_id', $envVariable->id, 'updated', $this->user->id);
    }

    public function test_can_delete_env_variable(): void
    {
        $envVariable = EnvVariable::factory()->create(['is_encrypted' => false]);

        $response = $this->deleteJson(route('env-variable.destroy', $envVariable));

        $response->assertStatus(200);

        $this->assertSoftDeleted('env_variables', [
            'id' => $envVariable->id,
            'deleted_by' => $this->user->id,
        ]);

        $this->assertLogRecord('env_variable_logs', 'env_variable_id', $envVariable->id, 'deleted', $this->user->id, [
            'expect_null_new_values' => true,
        ]);
    }

    public function test_encrypted_env_variables_are_handled_correctly(): void
    {
        $data = [
            'key' => 'ENCRYPTED_SECRET',
            'value' => 'sensitive_data',
            'type' => 'password',
            'description' => 'Encrypted test variable',
            'is_active' => true,
            'is_encrypted' => true,
        ];

        $response = $this->postJson(route('env-variable.store'), $data);

        $response->assertStatus(200);

        $envVariable = EnvVariable::where('key', 'ENCRYPTED_SECRET')->first();
        $this->assertTrue($envVariable->is_encrypted);
        $this->assertNotEquals('sensitive_data', $envVariable->getRawOriginal('value'));
        $this->assertEquals('sensitive_data', $envVariable->decrypted_value);
    }

    public function test_key_validation_enforces_proper_format(): void
    {
        $invalidKeys = [
            'lowercase_key',
            '123_STARTS_WITH_NUMBER',
            'KEY-WITH-DASHES',
            'key with spaces',
            'KEY.',
        ];

        foreach ($invalidKeys as $key) {
            $data = [
                'key' => $key,
                'value' => 'test_value',
                'type' => 'text',
                'is_active' => true,
            ];

            $response = $this->postJson(route('env-variable.store'), $data);
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['key']);
        }
    }

    public function test_can_create_env_variable_with_select_type_options(): void
    {
        $data = [
            'key' => 'SELECT_VAR',
            'value' => 'opt_b',
            'type' => 'select',
            'options' => '["opt_a", "opt_b"]',
            'is_active' => true,
            'is_encrypted' => false,
        ];

        $response = $this->postJson(route('env-variable.store'), $data);

        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $envVariable = EnvVariable::where('key', 'SELECT_VAR')->first();
        $this->assertSame(['opt_a', 'opt_b'], $envVariable->options);
    }

    public function test_can_update_env_variable_options(): void
    {
        $envVariable = EnvVariable::factory()->create(['is_encrypted' => false]);

        $data = [
            'key' => $envVariable->key,
            'value' => 'opt_two',
            'type' => 'select',
            'options' => '["opt_one", "opt_two"]',
            'description' => 'Updated description',
            'is_active' => true,
            'is_encrypted' => false,
        ];

        $response = $this->putJson(route('env-variable.update', $envVariable), $data);

        $response->assertStatus(200);

        $envVariable->refresh();
        $this->assertSame(['opt_one', 'opt_two'], $envVariable->options);
    }

    public function test_options_must_be_valid_json(): void
    {
        $data = [
            'key' => 'BAD_OPTIONS_VAR',
            'value' => 'x',
            'type' => 'select',
            'options' => 'not-json-at-all',
            'is_active' => true,
        ];

        $response = $this->postJson(route('env-variable.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['options']);
    }

    public function test_options_must_be_array_of_strings(): void
    {
        $data = [
            'key' => 'NUMERIC_OPTIONS_VAR',
            'value' => 'x',
            'type' => 'select',
            'options' => '[1, 2, 3]',
            'is_active' => true,
        ];

        $response = $this->postJson(route('env-variable.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['options.0']);
    }

    public function test_can_sync_env_file(): void
    {
        EnvVariable::factory()->create([
            'key' => 'SYNC_TEST_VAR',
            'value' => 'sync_value',
            'is_encrypted' => false,
        ]);

        $response = $this->postJson(route('env-variable.sync-to-env'));

        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
    }

    public function test_can_clear_cache(): void
    {
        $response = $this->postJson(route('env-variable.clear-cache'));

        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
    }

    public function test_can_dump_autoload(): void
    {
        $response = $this->postJson(route('env-variable.composer-dump'));

        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
    }

    public function test_activity_logs_track_changes_correctly(): void
    {
        $envVariable = EnvVariable::factory()->create([
            'key' => 'LOG_TEST_VAR',
            'value' => 'original_value',
            'description' => 'Original description',
            'is_encrypted' => false,
        ]);

        $updateData = [
            'key' => 'LOG_TEST_VAR',
            'value' => 'updated_value',
            'type' => 'text',
            'description' => 'Updated description',
            'is_active' => true,
            'is_encrypted' => false,
        ];

        $this->putJson(route('env-variable.update', $envVariable), $updateData);

        $this->assertLogRecord('env_variable_logs', 'env_variable_id', $envVariable->id, 'updated', $this->user->id);
    }

    public function test_cannot_create_duplicate_keys(): void
    {
        EnvVariable::factory()->create(['key' => 'DUPLICATE_KEY', 'is_encrypted' => false]);

        $data = [
            'key' => 'DUPLICATE_KEY',
            'value' => 'different_value',
            'type' => 'text',
            'is_active' => true,
        ];

        $response = $this->postJson(route('env-variable.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['key']);
    }
}
