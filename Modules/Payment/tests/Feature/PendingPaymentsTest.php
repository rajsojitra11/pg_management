<?php

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Role::firstOrCreate(
            ['name' => 'Pg_Admin', 'guard_name' => 'web'],
            ['title' => 'Pg_Admin', 'access_type' => 'both']
        );
        $this->user->assignRole('Pg_Admin');
        $this->actingAs($this->user);
        Sanctum::actingAs($this->user);
    }

    public function test_pending_lists_only_tenants_missing_current_billing_month(): void
    {
        $pg = PgManagement::create(['pg_name' => 'Test PG', 'owner_id' => $this->user->id]);
        $room = Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'A-101']);

        Tenant::create([
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'name' => 'Overdue Tenant',
            'checkin_date' => now()->subMonths(3)->startOfMonth(),
            'monthly_rent' => 5000,
        ]);

        $paid = Tenant::create([
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'name' => 'Paid Tenant',
            'checkin_date' => now()->subMonths(3)->startOfMonth(),
            'monthly_rent' => 4000,
        ]);

        Payment::create([
            'tenant_id' => $paid->id,
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'payment_date' => now(),
            'amount' => 4000,
            'payment_method' => 'Cash',
            'verified' => 'verified',
        ]);

        $response = $this->getJson('/api/v1/payments/pending');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Overdue Tenant', $data[0]['name']);
        $this->assertEquals(5000.0, $data[0]['monthly_rent']);
    }

    public function test_pending_returns_list_even_when_filtered_tenants_precede_kept_ones(): void
    {
        $pg = PgManagement::create(['pg_name' => 'Test PG', 'owner_id' => $this->user->id]);
        $room = Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'A-101']);

        $paid = Tenant::create([
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'name' => 'Aa Paid Tenant',
            'checkin_date' => now()->subMonths(3)->startOfMonth(),
            'monthly_rent' => 4000,
        ]);

        Payment::create([
            'tenant_id' => $paid->id,
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'payment_date' => now(),
            'amount' => 4000,
            'payment_method' => 'Cash',
            'verified' => 'verified',
        ]);

        Tenant::create([
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'name' => 'Zz Overdue Tenant',
            'checkin_date' => now()->subMonths(3)->startOfMonth(),
            'monthly_rent' => 5000,
        ]);

        $response = $this->getJson('/api/v1/payments/pending');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertTrue(array_is_list($data), 'pending data must be a JSON array, not an object');
        $this->assertCount(1, $data);
        $this->assertEquals('Zz Overdue Tenant', $data[0]['name']);
    }

    public function test_web_pending_payments_endpoint_is_not_matched_as_payment_show(): void
    {
        $route = app('router')
            ->getRoutes()
            ->match(Request::create('/payments/pending-payments', 'GET'));

        $this->assertSame('payment.pending.data', $route->getName());
    }

    public function test_pg_admin_lookup_only_returns_owned_pg_and_owned_rooms(): void
    {
        Auth::shouldUse('web');
        $this->actingAs($this->user, 'web');

        $ownedPg = PgManagement::create(['pg_name' => 'Owned PG', 'owner_id' => $this->user->id, 'status' => 'active']);
        $secondOwnedPg = PgManagement::create(['pg_name' => 'Second Owned PG', 'owner_id' => $this->user->id, 'status' => 'active']);
        $ownedRoom = Room::create(['pg_id' => $ownedPg->id, 'category_id' => 1, 'room_no' => 'A-101', 'status' => 'active']);

        $other = User::factory()->create();
        $otherPg = PgManagement::create(['pg_name' => 'Other PG', 'owner_id' => $other->id, 'status' => 'active']);
        Room::create(['pg_id' => $otherPg->id, 'category_id' => 1, 'room_no' => 'B-101', 'status' => 'active']);

        $this->getJson('/lookup/pg-list')
            ->assertOk()
            ->assertExactJson([
                ['value' => (string) $ownedPg->id, 'label' => 'Owned PG'],
                ['value' => (string) $secondOwnedPg->id, 'label' => 'Second Owned PG'],
            ]);

        $this->getJson('/lookup/rooms-by-pg?pg_id='.$ownedPg->id)
            ->assertOk()
            ->assertJsonFragment(['value' => (string) $ownedRoom->id, 'label' => 'A-101']);

        $this->getJson('/lookup/rooms-by-pg?pg_id='.$otherPg->id)
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_pending_is_scoped_to_owner_and_pg_id(): void
    {
        $pg = PgManagement::create(['pg_name' => 'Test PG', 'owner_id' => $this->user->id]);
        $room = Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'A-101']);
        Tenant::create([
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'name' => 'Owned Overdue',
            'checkin_date' => now()->subMonths(3)->startOfMonth(),
            'monthly_rent' => 1000,
        ]);

        $other = User::factory()->create();
        $otherPg = PgManagement::create(['pg_name' => 'Other PG', 'owner_id' => $other->id]);
        $otherRoom = Room::create(['pg_id' => $otherPg->id, 'category_id' => 1, 'room_no' => 'B-101']);
        Tenant::create([
            'pg_id' => $otherPg->id,
            'room_id' => $otherRoom->id,
            'name' => 'Foreign Overdue',
            'checkin_date' => now()->subMonths(3)->startOfMonth(),
            'monthly_rent' => 2000,
        ]);

        $this->getJson('/api/v1/payments/pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Owned Overdue');

        $this->getJson('/api/v1/payments/pending?pg_id='.$pg->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/payments/pending?pg_id='.$otherPg->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
