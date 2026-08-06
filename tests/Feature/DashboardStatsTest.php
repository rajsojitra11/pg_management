<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Complaint\Models\Complaint;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Role::firstOrCreate(
            ['name' => 'Pg_Admin', 'guard_name' => 'web'],
            ['title' => 'Pg_Admin', 'access_type' => 'mobile']
        );
        $this->user->assignRole('Pg_Admin');
        Sanctum::actingAs($this->user);
    }

    public function test_stats_returns_aggregate_counts(): void
    {
        $pg = PgManagement::create(['pg_name' => 'Test PG', 'owner_id' => $this->user->id]);

        $roomA = Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'A-101', 'bed_capacity' => 1]);
        $roomB = Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'A-102', 'bed_capacity' => 2]);
        $roomC = Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'A-103', 'bed_capacity' => 2]);

        $tenantA = Tenant::create(['pg_id' => $pg->id, 'room_id' => $roomA->id, 'name' => 'Tenant A', 'status' => 'active']);
        $tenantB = Tenant::create(['pg_id' => $pg->id, 'room_id' => $roomB->id, 'name' => 'Tenant B', 'status' => 'active']);

        Payment::create([
            'tenant_id' => $tenantA->id,
            'pg_id' => $pg->id,
            'room_id' => $roomA->id,
            'payment_date' => now(),
            'amount' => 5000,
            'payment_method' => 'Cash',
            'verified' => 'verified',
        ]);
        Payment::create([
            'tenant_id' => $tenantB->id,
            'pg_id' => $pg->id,
            'room_id' => $roomB->id,
            'payment_date' => now(),
            'amount' => 3000,
            'payment_method' => 'UPI',
            'verified' => 'pending',
        ]);

        $category = ServiceCategory::create(['service_category_name' => 'Repair']);
        $service = Service::create(['service_category_id' => $category->id, 'service_name' => 'Plumbing']);

        Complaint::create(['pg_id' => $pg->id, 'room_id' => $roomA->id, 'complaint_no' => 'CMP-001', 'service_category_id' => $category->id, 'service_id' => $service->id, 'complaint_date' => now(), 'note' => 'Broken tap', 'status' => 'pending']);
        Complaint::create(['pg_id' => $pg->id, 'room_id' => $roomB->id, 'complaint_no' => 'CMP-002', 'service_category_id' => $category->id, 'service_id' => $service->id, 'complaint_date' => now(), 'note' => 'Fixed', 'status' => 'resolved']);

        $response = $this->getJson(route('api.dashboard.stats', ['pg_id' => $pg->id]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'total_tenants' => 2,
                    'available_rooms' => 2,
                    'total_approved_payment' => 5000,
                    'open_complaints' => 1,
                ],
            ]);
    }

    public function test_stats_scopes_unauthorized_pg_rooms_away(): void
    {
        $pg = PgManagement::create(['pg_name' => 'Test PG', 'owner_id' => $this->user->id]);
        Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'A-101']);

        $response = $this->getJson(route('api.dashboard.stats', ['pg_id' => 99999]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'total_tenants' => 0,
                    'available_rooms' => 0,
                    'total_approved_payment' => 0,
                    'open_complaints' => 0,
                ],
            ]);
    }
}
