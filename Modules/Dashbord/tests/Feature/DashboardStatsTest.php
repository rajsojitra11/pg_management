<?php

namespace Modules\Dashbord\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
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
        Sanctum::actingAs($this->user);
    }

    private function createTenant(string $name, int $ownerId, float $monthlyRent = 0, ?Carbon $checkin = null): object
    {
        $pg = PgManagement::create(['pg_name' => 'PG '.$name, 'owner_id' => $ownerId]);
        $room = Room::create(['pg_id' => $pg->id, 'category_id' => 1, 'room_no' => 'R-'.substr(md5($name), 0, 4)]);
        $tenant = Tenant::create([
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'name' => $name,
            'checkin_date' => $checkin ?? now()->subMonths(3)->startOfMonth(),
            'monthly_rent' => $monthlyRent,
        ]);

        return (object) ['pg' => $pg, 'tenant' => $tenant];
    }

    public function test_stats_pending_payment_is_sum_of_rent_for_unpaid_billing_months(): void
    {
        $this->createTenant('No Payment', $this->user->id, 5000, now()->subMonths(3)->startOfMonth());

        $paid = $this->createTenant('Paid Current Month', $this->user->id, 9000, now()->subMonths(3)->startOfMonth());
        Payment::create([
            'tenant_id' => $paid->tenant->id,
            'pg_id' => $paid->pg->id,
            'room_id' => $paid->tenant->room_id,
            'payment_date' => now(),
            'amount' => 9000,
            'payment_method' => 'Cash',
            'verified' => 'verified',
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.total_pending_payment', 5000)
            ->assertJsonPath('data.total_approved_payment', 9000);
    }

    public function test_stats_pending_payment_is_scoped_to_owner_and_pg(): void
    {
        $this->createTenant('Owned Overdue', $this->user->id, 4000, now()->subMonths(3)->startOfMonth());

        $other = User::factory()->create();
        $this->createTenant('Foreign Overdue', $other->id, 7000, now()->subMonths(3)->startOfMonth());

        $owned = PgManagement::where('owner_id', $this->user->id)->first();

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_pending_payment', 4000);

        $this->getJson('/api/v1/dashboard?pg_id='.$owned->id)
            ->assertOk()
            ->assertJsonPath('data.total_pending_payment', 4000);
    }

    public function test_stats_pending_payment_excludes_tenants_checked_in_under_a_month(): void
    {
        $this->createTenant('Fresh Tenant', $this->user->id, 3000, now()->subDays(10));

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_pending_payment', 0);
    }
}
