<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\City\Models\City;
use Modules\Complaint\Models\Complaint;
use Modules\Country\Models\Country;
use Modules\Maintenance\Models\Maintenance;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Models\PgManagement;
use Modules\Report\Exports\PaymentReportExport;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomCategory;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\State\Models\State;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->createRoleWithPermissions('Report_Test', ['report-list'], $this->user);
    $this->actingAs($this->user);
});

afterEach(function () {
    $this->user->roles()->detach();
    $this->user->delete();
});

it('renders the report center landing page', function () {
    $this->get(route('report.index'))
        ->assertOk()
        ->assertSee('Report Center');
});

it('renders the tenant report page', function () {
    $this->get(route('report.tenants'))
        ->assertOk()
        ->assertSee('filter_form');
});

it('renders the payment report page', function () {
    $this->get(route('report.payments'))
        ->assertOk()
        ->assertSee('filter_form');
});

it('renders the complaint report page', function () {
    $this->get(route('report.complaints'))
        ->assertOk()
        ->assertSee('filter_form');
});

it('renders the maintenance report page', function () {
    $this->get(route('report.maintenance'))
        ->assertOk()
        ->assertSee('filter_form');
});

it('serves server-side data for the tenant report', function () {
    $this->getJson(route('report.tenants'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

it('serves server-side data for the payment report', function () {
    $this->getJson(route('report.payments'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

it('only includes approved payments and groups them room-wise', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'status' => 'active',
        'pg_id' => $pg->id,
        'room_id' => $room->id,
    ]);

    Payment::create([
        'tenant_id' => $tenant->id,
        'pg_id' => $pg->id,
        'room_id' => $room->id,
        'payment_date' => '2024-06-15',
        'amount' => 5000,
        'payment_method' => 'UPI',
        'reference_no' => 'REF-APPROVED',
        'verified' => 'verified',
    ]);

    Payment::create([
        'tenant_id' => $tenant->id,
        'pg_id' => $pg->id,
        'room_id' => $room->id,
        'payment_date' => '2024-06-16',
        'amount' => 6000,
        'payment_method' => 'UPI',
        'reference_no' => 'REF-PENDING',
        'verified' => 'pending',
    ]);

    $data = $this->getJson(route('report.payments'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['room_no'])->toBe($room->room_no);
    expect($data[0]['tenant_name'])->toBe($tenant->name);
    expect((int) $data[0]['payment_count'])->toBe(1);
    expect($data[0]['total_amount'])->toBe('₹5,000.00');
});

it('filters the payment report by the selected tenant', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant1 = Tenant::create(['name' => 'Alpha Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room1->id]);
    $tenant2 = Tenant::create(['name' => 'Beta Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room2->id]);

    Payment::create(['tenant_id' => $tenant1->id, 'pg_id' => $pg->id, 'room_id' => $room1->id, 'payment_date' => '2024-06-15', 'amount' => 3000, 'payment_method' => 'UPI', 'verified' => 'verified']);
    Payment::create(['tenant_id' => $tenant2->id, 'pg_id' => $pg->id, 'room_id' => $room2->id, 'payment_date' => '2024-06-16', 'amount' => 7000, 'payment_method' => 'UPI', 'verified' => 'verified']);

    $data = $this->getJson(route('report.payments').'?filter_tenant='.$tenant1->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['room_no'])->toBe($room1->room_no);
    expect($data[0]['tenant_name'])->toBe($tenant1->name);
    expect((int) $data[0]['payment_count'])->toBe(1);
    expect($data[0]['total_amount'])->toBe('₹3,000.00');
});

it('filters the payment report by the selected room', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant1 = Tenant::create(['name' => 'Alpha Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room1->id]);
    $tenant2 = Tenant::create(['name' => 'Beta Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room2->id]);

    Payment::create(['tenant_id' => $tenant1->id, 'pg_id' => $pg->id, 'room_id' => $room1->id, 'payment_date' => '2024-06-15', 'amount' => 3000, 'payment_method' => 'UPI', 'verified' => 'verified']);
    Payment::create(['tenant_id' => $tenant2->id, 'pg_id' => $pg->id, 'room_id' => $room2->id, 'payment_date' => '2024-06-16', 'amount' => 7000, 'payment_method' => 'UPI', 'verified' => 'verified']);

    $data = $this->getJson(route('report.payments').'?filter_room='.$room2->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['room_no'])->toBe($room2->room_no);
    expect($data[0]['tenant_name'])->toBe($tenant2->name);
    expect((int) $data[0]['payment_count'])->toBe(1);
    expect($data[0]['total_amount'])->toBe('₹7,000.00');
});

it('serves server-side data for the complaint report', function () {
    $this->getJson(route('report.complaints'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

it('serves server-side data for the maintenance report', function () {
    $this->getJson(route('report.maintenance'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

it('filters the complaint report by the selected room', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $serviceCategory = ServiceCategory::factory()->create();
    $service1 = Service::factory()->create(['service_category_id' => $serviceCategory->id]);
    $service2 = Service::factory()->create(['service_category_id' => $serviceCategory->id]);

    Complaint::create(['complaint_no' => 'CMP-0001', 'pg_id' => $pg->id, 'room_id' => $room1->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service1->id, 'complaint_date' => '2024-06-15', 'note' => 'AC not working', 'status' => 'pending', 'created_by' => $this->user->id]);
    Complaint::create(['complaint_no' => 'CMP-0002', 'pg_id' => $pg->id, 'room_id' => $room2->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service2->id, 'complaint_date' => '2024-06-16', 'note' => 'Leakage', 'status' => 'pending', 'created_by' => $this->user->id]);

    $data = $this->getJson(route('report.complaints').'?filter_room='.$room2->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['room_no'])->toBe($room2->room_no);
    expect($data[0]['note'])->toBe('Leakage');
});

it('filters the maintenance report by the selected room', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $serviceCategory = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $serviceCategory->id]);

    $complaint1 = Complaint::create(['complaint_no' => 'CMP-0003', 'pg_id' => $pg->id, 'room_id' => $room1->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service->id, 'complaint_date' => '2024-06-15', 'note' => 'AC not working', 'status' => 'resolved', 'created_by' => $this->user->id]);
    $complaint2 = Complaint::create(['complaint_no' => 'CMP-0004', 'pg_id' => $pg->id, 'room_id' => $room2->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service->id, 'complaint_date' => '2024-06-16', 'note' => 'Leakage', 'status' => 'resolved', 'created_by' => $this->user->id]);

    Maintenance::create(['maintenance_no' => 'MNT-0001', 'complaint_id' => $complaint1->id, 'cost' => 1200, 'description' => 'Compressor replaced', 'maintenance_date' => '2024-06-18', 'status' => 'completed']);
    Maintenance::create(['maintenance_no' => 'MNT-0002', 'complaint_id' => $complaint2->id, 'cost' => 800, 'description' => 'Pipe fixed', 'maintenance_date' => '2024-06-19', 'status' => 'completed']);

    $data = $this->getJson(route('report.maintenance').'?filter_room='.$room2->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['room_no'])->toBe($room2->room_no);
    expect($data[0]['description'])->toBe('Pipe fixed');
});

it('respects date range and status filters on the tenant report', function () {
    $this->getJson(route('report.tenants').'?filter_from=2024-01-01&filter_to=2025-01-01&filter_status=active', ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

it('filters the tenant report by the selected tenant', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant1 = Tenant::create(['name' => 'Gamma Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room1->id]);
    $tenant2 = Tenant::create(['name' => 'Delta Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room2->id]);

    $data = $this->getJson(route('report.tenants').'?filter_tenant='.$tenant2->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe($tenant2->name);
    expect($data[0]['room_no'])->toBe($room2->room_no);
});

it('filters the tenant report by the selected room', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant1 = Tenant::create(['name' => 'Echo Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room1->id]);
    $tenant2 = Tenant::create(['name' => 'Foxtrot Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room2->id]);

    $data = $this->getJson(route('report.tenants').'?filter_room='.$room1->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe($tenant1->name);
    expect($data[0]['room_no'])->toBe($room1->room_no);
});

it('downloads the tenant report as an excel file', function () {
    $this->get(route('report.tenants.export'))
        ->assertOk()
        ->assertDownload();
});

it('includes all tenant details as columns in the tenant excel export', function () {
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $city = City::factory()->create(['state_id' => $state->id]);

    $tenant = Tenant::create([
        'name' => 'Detail Tenant',
        'email' => 'detail@example.com',
        'phone' => '9998887770',
        'status' => 'active',
        'pg_id' => $pg->id,
        'room_id' => $room->id,
        'bed_no' => 'B-12',
        'date_of_birth' => '1998-04-12',
        'gender' => 'male',
        'occupation' => 'Software Engineer',
        'address' => 'Marine Drive, Mumbai',
        'checkin_date' => '2024-06-01',
        'expected_checkout_date' => '2025-06-01',
        'monthly_rent' => 4500,
        'security_deposit' => 9000,
        'payment_method' => 'UPI',
        'id_proof_type' => 'Aadhaar',
        'id_proof_number' => '1234-5678-9012',
        'emergency_contact_name' => 'Jane Doe',
        'emergency_relation' => 'Sister',
        'emergency_contact_number' => '9000123456',
        'permanent_state_id' => $state->id,
        'permanent_city_id' => $city->id,
        'permanent_address' => 'Green Hills, Kerala',
        'additional_notes' => 'Vegetarian',
    ]);

    $response = $this->get(route('report.tenants.export'));
    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'tenant_export_').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    $sheet = (new Xlsx)->load($path)->getActiveSheet();
    $headings = $sheet->rangeToArray('A1:Z1')[0];
    $row = $sheet->rangeToArray('A2:Z2')[0];

    expect($headings)->toContain('PG');
    expect($headings)->toContain('Bed No');
    expect($headings)->toContain('Date of Birth');
    expect($headings)->toContain('Occupation');
    expect($headings)->toContain('Security Deposit');
    expect($headings)->toContain('Emergency Contact Name');
    expect($headings)->toContain('Permanent State');
    expect($headings)->toContain('Additional Notes');

    $tenantIndex = array_search('Tenant', $headings, true);
    $bedIndex = array_search('Bed No', $headings, true);
    $occupationIndex = array_search('Occupation', $headings, true);
    $dobIndex = array_search('Date of Birth', $headings, true);
    $stateIndex = array_search('Permanent State', $headings, true);

    expect($row[$tenantIndex])->toBe('Detail Tenant');
    expect($row[$bedIndex])->toBe('B-12');
    expect($row[$occupationIndex])->toBe('Software Engineer');
    expect($row[$dobIndex])->toBe('12-04-1998');
    expect($row[$stateIndex])->toBe($state->name);

    unlink($path);
});

it('downloads the payment report as an excel file', function () {
    $this->get(route('report.payments.export'))
        ->assertOk()
        ->assertDownload();
});

it('includes the last 12 months as columns in the payment excel export', function () {
    $export = new PaymentReportExport(Payment::query()->where('verified', 'verified'), collect());

    $headings = $export->headings();

    expect($headings)->toHaveCount(17);
    expect($headings[0])->toBe('S.No');
    expect($headings[1])->toBe('Room No');
    expect($headings[4])->toBe('Total Amount');
    expect($headings[5])->toBe(now()->startOfMonth()->subMonths(11)->format('M-y'));
    expect($headings)->toContain(now()->format('M-y'));
});

it('exports every payment record vertically when a tenant is selected', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant = Tenant::create(['name' => 'Vertical Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room->id]);
    $otherTenant = Tenant::create(['name' => 'Other Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room->id]);

    Payment::create(['tenant_id' => $tenant->id, 'pg_id' => $pg->id, 'room_id' => $room->id, 'payment_date' => '2024-06-10', 'amount' => 3000, 'payment_method' => 'UPI', 'verified' => 'verified']);
    Payment::create(['tenant_id' => $tenant->id, 'pg_id' => $pg->id, 'room_id' => $room->id, 'payment_date' => '2024-07-10', 'amount' => 3500, 'payment_method' => 'Bank Transfer', 'reference_no' => 'NEFT-001', 'verified' => 'verified']);
    Payment::create(['tenant_id' => $otherTenant->id, 'pg_id' => $pg->id, 'room_id' => $room->id, 'payment_date' => '2024-06-12', 'amount' => 9000, 'payment_method' => 'UPI', 'verified' => 'verified']);

    $response = $this->get(route('report.payments.export').'?filter_tenant='.$tenant->id);
    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'pay_detail_').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    $sheet = (new Xlsx)->load($path)->getActiveSheet();
    $headings = $sheet->rangeToArray('A1:H1')[0];
    $rows = $sheet->toArray();

    expect($headings)->toContain('Payment Date');
    expect($headings)->toContain('Payment Method');
    expect($headings)->toContain('Reference No');
    expect($headings)->toContain('Amount');
    expect($rows)->toHaveCount(3);
    expect($rows[1])->toContain('Vertical Tenant');
    expect($rows[2])->toContain('Vertical Tenant');
    expect($rows[1])->toContain('₹3,000.00');
    expect($rows[2])->toContain('₹3,500.00');
    expect($rows[2])->toContain('NEFT-001');

    unlink($path);
});

it('downloads the complaint report as an excel file', function () {
    $this->get(route('report.complaints.export'))
        ->assertOk()
        ->assertDownload();
});

it('downloads the maintenance report as an excel file', function () {
    $this->get(route('report.maintenance.export'))
        ->assertOk()
        ->assertDownload();
});

it('places each tenant rent amount in its month column in the payment export', function () {
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant = Tenant::create([
        'name' => 'Monthly Tenant',
        'status' => 'active',
        'pg_id' => $pg->id,
        'room_id' => $room->id,
    ]);

    Payment::create([
        'tenant_id' => $tenant->id,
        'pg_id' => $pg->id,
        'room_id' => $room->id,
        'payment_date' => now()->format('Y-m-d'),
        'amount' => 4200,
        'payment_method' => 'UPI',
        'verified' => 'verified',
    ]);

    $response = $this->get(route('report.payments.export'));
    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'pay_export_').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    $sheet = (new Xlsx)->load($path)->getActiveSheet();
    $headings = $sheet->rangeToArray('A1:Q1')[0];
    $currentMonthIndex = array_search(now()->format('M-y'), $headings, true);
    $column = Coordinate::stringFromColumnIndex($currentMonthIndex + 1).'2';
    $rentValue = $sheet->getCell($column)->getValue();

    expect($currentMonthIndex)->not->toBe(false);
    expect($rentValue)->toBe('4,200.00');

    unlink($path);
});

it('redirects guests away from the report pages', function () {
    auth()->logout();

    $this->get(route('report.index'))->assertRedirect(route('login'));
    $this->get(route('report.tenants.export'))->assertRedirect(route('login'));
});

it('returns report center counts through the api', function () {
    Sanctum::actingAs($this->user, ['report-list']);

    $this->getJson(route('api.report.summary'))
        ->assertOk()
        ->assertJsonStructure(['data' => ['tenant', 'payment', 'complaint', 'maintenance']]);
});

it('returns tenant report rows through the api', function () {
    Sanctum::actingAs($this->user, ['report-list']);

    $this->getJson(route('api.report.tenants'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('returns grouped payment report rows through the api', function () {
    Sanctum::actingAs($this->user, ['report-list']);

    $this->getJson(route('api.report.payments'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('returns complaint report rows through the api', function () {
    Sanctum::actingAs($this->user, ['report-list']);

    $this->getJson(route('api.report.complaints'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('returns maintenance report rows through the api', function () {
    Sanctum::actingAs($this->user, ['report-list']);

    $this->getJson(route('api.report.maintenance'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('filters the tenant report api by search and status', function () {
    Sanctum::actingAs($this->user, ['report-list']);
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    Tenant::create(['name' => 'Alpha Search Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room->id]);
    Tenant::create(['name' => 'Beta Inactive Tenant', 'status' => 'inactive', 'pg_id' => $pg->id, 'room_id' => $room->id]);

    $data = $this->getJson(route('api.report.tenants').'?search=Search&status=active')
        ->assertOk()
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Alpha Search Tenant');
    expect($data[0]['room_no'])->toBe($room->room_no);
});

it('only includes approved payments grouped in the payment report api', function () {
    Sanctum::actingAs($this->user, ['report-list']);
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $tenant = Tenant::create(['name' => 'API Tenant', 'status' => 'active', 'pg_id' => $pg->id, 'room_id' => $room->id]);

    Payment::create(['tenant_id' => $tenant->id, 'pg_id' => $pg->id, 'room_id' => $room->id, 'payment_date' => '2024-06-15', 'amount' => 5000, 'payment_method' => 'UPI', 'verified' => 'verified']);
    Payment::create(['tenant_id' => $tenant->id, 'pg_id' => $pg->id, 'room_id' => $room->id, 'payment_date' => '2024-06-16', 'amount' => 6000, 'payment_method' => 'UPI', 'verified' => 'pending']);

    $data = $this->getJson(route('api.report.payments'))
        ->assertOk()
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['tenant_name'])->toBe($tenant->name);
    expect((int) $data[0]['payment_count'])->toBe(1);
    expect((float) $data[0]['total_amount'])->toBe(5000.0);
});

it('filters the complaint report api by room', function () {
    Sanctum::actingAs($this->user, ['report-list']);
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $serviceCategory = ServiceCategory::factory()->create();
    $service1 = Service::factory()->create(['service_category_id' => $serviceCategory->id]);
    $service2 = Service::factory()->create(['service_category_id' => $serviceCategory->id]);

    Complaint::create(['complaint_no' => 'CMP-API1', 'pg_id' => $pg->id, 'room_id' => $room1->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service1->id, 'complaint_date' => '2024-06-15', 'note' => 'AC not working', 'status' => 'pending', 'created_by' => $this->user->id]);
    Complaint::create(['complaint_no' => 'CMP-API2', 'pg_id' => $pg->id, 'room_id' => $room2->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service2->id, 'complaint_date' => '2024-06-16', 'note' => 'Leakage', 'status' => 'pending', 'created_by' => $this->user->id]);

    $data = $this->getJson(route('api.report.complaints').'?room_id='.$room2->id)
        ->assertOk()
        ->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['room_no'])->toBe($room2->room_no);
    expect($data[0]['note'])->toBe('Leakage');
});

it('filters the maintenance report api by room', function () {
    Sanctum::actingAs($this->user, ['report-list']);
    $category = RoomCategory::factory()->create();
    $pg = PgManagement::factory()->create();
    $room1 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $room2 = Room::factory()->create(['pg_id' => $pg->id, 'category_id' => $category->id]);
    $serviceCategory = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $serviceCategory->id]);

    $complaint1 = Complaint::create(['complaint_no' => 'CMP-MNT1', 'pg_id' => $pg->id, 'room_id' => $room1->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service->id, 'complaint_date' => '2024-06-15', 'note' => 'AC not working', 'status' => 'resolved', 'created_by' => $this->user->id]);
    $complaint2 = Complaint::create(['complaint_no' => 'CMP-MNT2', 'pg_id' => $pg->id, 'room_id' => $room2->id, 'service_category_id' => $serviceCategory->id, 'service_id' => $service->id, 'complaint_date' => '2024-06-16', 'note' => 'Leakage', 'status' => 'resolved', 'created_by' => $this->user->id]);

    Maintenance::create(['maintenance_no' => 'MNT-API1', 'complaint_id' => $complaint1->id, 'cost' => 1200, 'description' => 'Compressor replaced', 'maintenance_date' => '2024-06-18', 'status' => 'completed']);
    Maintenance::create(['maintenance_no' => 'MNT-API2', 'complaint_id' => $complaint2->id, 'cost' => 800, 'description' => 'Pipe fixed', 'maintenance_date' => '2024-06-19', 'status' => 'completed']);

    $this->getJson(route('api.report.maintenance').'?room_id='.$room2->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.room_no', $room2->room_no)
        ->assertJsonPath('data.0.description', 'Pipe fixed');
});
