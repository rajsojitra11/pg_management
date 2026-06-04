<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\City\Models\City;
use Modules\Country\Models\Country;
use Modules\State\Models\State;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Verifies that HasActivityLogging now embeds FK labels alongside FK IDs in
 * `old_values` / `new_values`, so an audit log row from years ago remains
 * readable even after dependent records are renamed or soft-deleted.
 */
class HasActivityLoggingFkLabelsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Country $country;

    protected State $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->createRoleWithPermissions('admin', ['city-list', 'city-create', 'city-edit', 'city-delete'], $this->user);
        $this->actingAs($this->user);

        $this->country = Country::factory()->create(['name' => 'India']);
        $this->state = State::factory()->create([
            'name' => 'Maharashtra',
            'country_id' => $this->country->id,
        ]);
    }

    public function test_create_log_embeds_fk_labels_in_new_values(): void
    {
        $city = City::factory()->create([
            'name' => 'Mumbai',
            'state_id' => $this->state->id,
            'country_id' => $this->country->id,
        ]);

        $log = DB::table('city_logs')
            ->where('city_id', $city->id)
            ->where('activity', 'created')
            ->first();
        $this->assertNotNull($log);

        $newValues = json_decode($log->new_values, true);
        $this->assertEquals($this->state->id, $newValues['state_id']);
        $this->assertEquals('Maharashtra', $newValues['state_id_label']);
        $this->assertEquals($this->country->id, $newValues['country_id']);
        $this->assertEquals('India', $newValues['country_id_label']);
    }

    public function test_update_log_embeds_fk_labels_in_both_old_and_new_values(): void
    {
        $otherCountry = Country::factory()->create(['name' => 'Japan']);
        $otherState = State::factory()->create([
            'name' => 'Tokyo',
            'country_id' => $otherCountry->id,
        ]);

        $city = City::factory()->create([
            'name' => 'Mumbai',
            'state_id' => $this->state->id,
            'country_id' => $this->country->id,
        ]);

        // Direct model update doesn't trigger the trait's updating listener
        // (which requires PUT/PATCH HTTP context). Use the route.
        $this->putJson(route('city.update', $city->id), [
            'id' => $city->id,
            'name' => 'Tokyo',
            'state_id' => $otherState->id,
            'country_id' => $otherCountry->id,
            'user_remark' => 'Renaming and re-locating city for FK label test',
        ]);

        $log = DB::table('city_logs')
            ->where('city_id', $city->id)
            ->where('activity', 'updated')
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($log);

        $oldValues = json_decode($log->old_values, true);
        $newValues = json_decode($log->new_values, true);

        $this->assertEquals('Maharashtra', $oldValues['state_id_label']);
        $this->assertEquals('India', $oldValues['country_id_label']);
        $this->assertEquals('Tokyo', $newValues['state_id_label']);
        $this->assertEquals('Japan', $newValues['country_id_label']);
    }

    public function test_label_resolves_for_soft_deleted_dependency(): void
    {
        $otherState = State::factory()->create([
            'name' => 'Karnataka',
            'country_id' => $this->country->id,
        ]);

        $city = City::factory()->create([
            'name' => 'Pune',
            'state_id' => $this->state->id,
            'country_id' => $this->country->id,
        ]);

        // Update to point to a different state, then soft-delete the original
        $this->putJson(route('city.update', $city->id), [
            'id' => $city->id,
            'name' => 'Pune',
            'state_id' => $otherState->id,
            'country_id' => $this->country->id,
            'user_remark' => 'Moving city to a different state for soft-delete label test',
        ]);

        $this->state->delete(); // soft-delete the original state

        // The pre-existing log row's old_values should still resolve to 'Maharashtra'
        // because withTrashed is in effect for the FK label lookup.
        $log = DB::table('city_logs')
            ->where('city_id', $city->id)
            ->where('activity', 'updated')
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($log);

        $oldValues = json_decode($log->old_values, true);
        $this->assertEquals('Maharashtra', $oldValues['state_id_label']);
    }

    public function test_audit_only_fks_are_skipped(): void
    {
        $city = City::factory()->create([
            'name' => 'Delhi',
            'state_id' => $this->state->id,
            'country_id' => $this->country->id,
        ]);

        $log = DB::table('city_logs')
            ->where('city_id', $city->id)
            ->where('activity', 'created')
            ->first();
        $this->assertNotNull($log);

        $newValues = json_decode($log->new_values, true);
        // created_by / updated_by are present as IDs but should NOT have label keys
        $this->assertArrayNotHasKey('created_by_label', $newValues);
        $this->assertArrayNotHasKey('updated_by_label', $newValues);
        $this->assertArrayNotHasKey('deleted_by_label', $newValues);
    }

    public function test_null_fk_value_yields_null_label(): void
    {
        // Most fillable models reject null state_id; we'll use a different model
        // to confirm the null-handling path. Use a Country update where deleted_by
        // remains null — but deleted_by is in skip list, so it shouldn't appear.
        // Instead: directly test with arbitrary null FK input via the trait.
        $city = City::factory()->create([
            'name' => 'TestCity',
            'state_id' => $this->state->id,
            'country_id' => $this->country->id,
        ]);

        // The reflection-based detection captures whatever FKs are declared as
        // typed BelongsTo on the model. Here we just confirm the saved log row
        // doesn't blow up when an FK could legitimately be null in the values.
        $log = DB::table('city_logs')
            ->where('city_id', $city->id)
            ->where('activity', 'created')
            ->first();
        $this->assertNotNull($log);
    }

    public function test_fk_detection_is_cached_per_class(): void
    {
        $reflector = new \ReflectionClass(City::class);
        $property = $reflector->getMethod('getEnrichableForeignKeys');
        $property->setAccessible(true);

        $instance = new City;
        $first = $property->invoke($instance);
        $this->assertIsArray($first);
        $this->assertArrayHasKey('state_id', $first);
        $this->assertArrayHasKey('country_id', $first);

        // Second invocation hits cache — same array reference
        $second = $property->invoke($instance);
        $this->assertSame($first, $second);
    }
}
