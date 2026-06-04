<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\City\Models\City;
use Modules\Country\Models\Country;
use Modules\Currency\Models\Currency;
use Modules\Item\Models\Item;
use Modules\State\Models\State;
use Modules\Unit\Models\Unit;
use Modules\User\Models\User;
use Modules\Year\Models\Year;
use Tests\TestCase;

/**
 * Feature tests for the canonical LookupController endpoints.
 *
 * Each endpoint asserts:
 *   1. 401 when unauthenticated (one representative — middleware applies uniformly)
 *   2. 200 + bare-array `[{value, label}]` envelope when authenticated
 *   3. `?q=` substring filter narrows results
 *   4. Endpoint-specific FK / scope filter (where applicable)
 */
class LookupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── Auth gate (representative endpoint — middleware applies uniformly) ──

    public function test_unauthenticated_request_to_states_returns_401(): void
    {
        $response = $this->getJson(route('lookup.states'));
        $response->assertStatus(401);
    }

    // ── countries ───────────────────────────────────────────────────────────

    public function test_countries_returns_envelope(): void
    {
        Country::create(['name' => 'India', 'code' => 'IN']);
        Country::create(['name' => 'United States', 'code' => 'US']);

        $response = $this->actingAs($this->user)->getJson(route('lookup.countries'));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(2, count($data));
        $this->assertEquals(['value', 'label'], array_keys($data[0]));
    }

    public function test_countries_q_filter(): void
    {
        Country::create(['name' => 'India', 'code' => 'IN']);
        Country::create(['name' => 'Japan', 'code' => 'JP']);

        $response = $this->actingAs($this->user)->getJson(route('lookup.countries', ['q' => 'Ind']));
        $labels = collect($response->json())->pluck('label')->all();
        $this->assertContains('India', $labels);
        $this->assertNotContains('Japan', $labels);
    }

    // ── states (FK filter) ──────────────────────────────────────────────────

    public function test_states_filters_by_country_id(): void
    {
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $usa = Country::create(['name' => 'United States', 'code' => 'US']);
        State::create(['name' => 'Gujarat', 'code' => 'GJ', 'country_id' => $india->id]);
        State::create(['name' => 'Maharashtra', 'code' => 'MH', 'country_id' => $india->id]);
        State::create(['name' => 'California', 'code' => 'CA', 'country_id' => $usa->id]);

        $response = $this->actingAs($this->user)->getJson(route('lookup.states', ['country_id' => $india->id]));
        $labels = collect($response->json())->pluck('label')->all();
        $this->assertContains('Gujarat', $labels);
        $this->assertContains('Maharashtra', $labels);
        $this->assertNotContains('California', $labels);
    }

    // ── cities (FK filter) ──────────────────────────────────────────────────

    public function test_cities_filters_by_state_id(): void
    {
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $gj = State::create(['name' => 'Gujarat', 'code' => 'GJ', 'country_id' => $india->id]);
        $mh = State::create(['name' => 'Maharashtra', 'code' => 'MH', 'country_id' => $india->id]);
        City::create(['name' => 'Ahmedabad', 'state_id' => $gj->id, 'country_id' => $india->id]);
        City::create(['name' => 'Mumbai', 'state_id' => $mh->id, 'country_id' => $india->id]);

        $response = $this->actingAs($this->user)->getJson(route('lookup.cities', ['state_id' => $gj->id]));
        $labels = collect($response->json())->pluck('label')->all();
        $this->assertContains('Ahmedabad', $labels);
        $this->assertNotContains('Mumbai', $labels);
    }

    // ── currencies ──────────────────────────────────────────────────────────

    public function test_currencies_returns_envelope(): void
    {
        Currency::create(['currency_name' => 'Indian Rupee', 'currency_symbol' => '₹']);

        $response = $this->actingAs($this->user)->getJson(route('lookup.currencies'));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertEquals(['value', 'label'], array_keys($data[0]));
    }

    // ── units ───────────────────────────────────────────────────────────────

    public function test_units_returns_envelope(): void
    {
        Unit::create(['name' => 'KG', 'unit_value' => '1']);

        $response = $this->actingAs($this->user)->getJson(route('lookup.units'));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertEquals(['value', 'label'], array_keys($data[0]));
    }

    // ── years ───────────────────────────────────────────────────────────────

    public function test_years_returns_envelope(): void
    {
        Year::create(['name' => '2025-2026', 'full_short' => '2025-26', 'short_full' => '25-2026', 'short_short' => '25-26', 'full_full' => '2025-2026', 'short' => '26', 'full' => '2026', 'set_default' => 1]);

        $response = $this->actingAs($this->user)->getJson(route('lookup.years'));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertEquals(['value', 'label'], array_keys($data[0]));
    }

    // ── items ───────────────────────────────────────────────────────────────

    public function test_items_returns_envelope(): void
    {
        Item::factory()->create(['name' => 'Widget Alpha']);
        Item::factory()->create(['name' => 'Widget Beta']);

        $response = $this->actingAs($this->user)->getJson(route('lookup.items'));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(2, count($data));
        $this->assertEquals(['value', 'label'], array_keys($data[0]));
    }
}
