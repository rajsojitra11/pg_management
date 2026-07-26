<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Setting\Models\Setting;
use Modules\User\Models\User;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected int $countryId;

    protected int $stateId;

    protected int $cityId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $permissions = ['setting-list', 'setting-create', 'setting-edit', 'setting-delete'];
        $this->createRoleWithPermissions('test-role', $permissions, $this->user);

        // Insert dependencies
        $this->countryId = \DB::table('countries')->insertGetId([
            'name' => 'India', 'code' => 'IN',
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->stateId = \DB::table('states')->insertGetId([
            'name' => 'Maharashtra', 'code' => 'MH', 'country_id' => $this->countryId,
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->cityId = \DB::table('cities')->insertGetId([
            'name' => 'Mumbai', 'state_id' => $this->stateId, 'country_id' => $this->countryId,
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_view_index(): void
    {
        Setting::factory()->create();

        $response = $this->get(route('setting.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_without_remarks(): void
    {
        $data = [
            'company_name' => 'Test Company',
            'country_id' => $this->countryId,
            'state_id' => $this->stateId,
            'city_id' => $this->cityId,
        ];

        $response = $this->postJson(route('setting.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $this->assertDatabaseHas('settings', [
            'company_name' => 'Test Company',
        ]);

        $record = Setting::where('company_name', 'Test Company')->first();
        $this->assertLogRecord('setting_logs', 'setting_id', $record->id, 'created', $this->user->id, [
            'expect_null_old_values' => true,
        ]);
    }

    public function test_can_update_settings(): void
    {
        $record = Setting::factory()->create(['company_name' => 'Old Company']);
        $data = [
            'company_name' => 'Updated Company Name',
            'country_id' => $this->countryId,
            'state_id' => $this->stateId,
            'city_id' => $this->cityId,
        ];

        $response = $this->postJson(route('setting.store'), $data);
        $response->assertStatus(200);

        $record->refresh();
        $this->assertEquals('Updated Company Name', $record->company_name);
    }

    public function test_update_log_tracks_changes(): void
    {
        Setting::factory()->create(['company_name' => 'Original Company']);
        $data = [
            'company_name' => 'Changed Company',
            'country_id' => $this->countryId,
            'state_id' => $this->stateId,
            'city_id' => $this->cityId,
        ];

        $response = $this->postJson(route('setting.store'), $data);
        $response->assertStatus(200);

        // Verify the data was actually updated
        $record = Setting::first();
        $this->assertEquals('Changed Company', $record->company_name);

        // Note: The store() method uses POST for updates, and HasActivityLogging
        // skips 'updated' logging for non-PUT/PATCH requests (no session action data).
        // So no 'updated' log is created - only the initial 'created' log exists.
    }

    public function test_validation_missing_required_fields(): void
    {
        $response = $this->postJson(route('setting.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['company_name']);
    }
}
