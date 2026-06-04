<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\OrderForm\Models\OrderForm;
use Modules\PostPressCategory\Models\PostPressCategory;
use Modules\User\Models\User;
use Tests\TestCase;

class OrderFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected int $yearId;

    protected int $clientId;

    protected int $paperId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $permissions = ['order-form-list', 'order-form-create', 'order-form-edit', 'order-form-delete'];
        $this->createRoleWithPermissions('test-orderform-role', $permissions, $this->user);

        $this->yearId = $this->seedYear();

        DB::table('countries')->insert([
            'id' => 1, 'name' => 'India', 'code' => 'IN',
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('states')->insert([
            'id' => 1, 'name' => 'Gujarat', 'code' => 'GJ', 'country_id' => 1,
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('cities')->insert([
            'id' => 1, 'name' => 'Ahmedabad', 'state_id' => 1, 'country_id' => 1,
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->clientId = DB::table('clients')->insertGetId([
            'name' => 'Test Client', 'mobile' => '9999999999',
            'country_id' => 1, 'state_id' => 1, 'city_id' => 1,
            'status' => 'Active',
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->paperId = DB::table('papers')->insertGetId([
            'name' => 'Art Paper', 'status' => 'Active',
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Seed the 4 post-press categories (matches PostPressCategoryDatabaseSeeder).
        foreach ([
            ['slug' => 'lamination', 'name' => 'Lamination', 'sort' => 1],
            ['slug' => 'postpress',  'name' => 'Post Press', 'sort' => 2],
            ['slug' => 'process',    'name' => 'Process',    'sort' => 3],
            ['slug' => 'uv',         'name' => 'UV',         'sort' => 4],
        ] as $row) {
            PostPressCategory::firstOrCreate(
                ['slug' => $row['slug']],
                ['name' => $row['name'], 'sort' => $row['sort'], 'status' => 'Active']
            );
        }

        $this->actingAs($this->user);
    }

    public function test_index_page_renders(): void
    {
        $response = $this->get(route('orderform.index'));
        $response->assertStatus(200);
    }

    public function test_can_store_a_full_order_form(): void
    {
        $payload = [
            'order_date' => now()->toDateString(),
            'client_id' => $this->clientId,
            'job_name' => 'Smoke test job',
            'year_id' => $this->yearId,
            'papers' => [
                [
                    'paper_id' => $this->paperId,
                    'qty' => 500,
                ],
            ],
            'printing_jobs' => [
                [
                    'job_description' => 'Card A',
                    'final_sheets' => 100,
                    'wastage_sheets' => 20,
                    'total_sheets' => 120,
                    'plate_washing' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('orderform.store'), $payload);
        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);

        $orderForm = OrderForm::first();
        $this->assertNotNull($orderForm);
        $this->assertSame('Smoke test job', $orderForm->job_name);
        $this->assertSame($this->clientId, $orderForm->client_id);
        $this->assertStringStartsWith('VO', $orderForm->order_no);

        $this->assertDatabaseHas('order_form_papers', [
            'order_form_id' => $orderForm->id,
            'paper_id' => $this->paperId,
            'qty' => 500,
        ]);
        $this->assertDatabaseHas('order_form_printing_jobs', [
            'order_form_id' => $orderForm->id,
            'card_label' => 'A',
            'final_sheets' => 100,
            'wastage_sheets' => 20,
        ]);
    }

    public function test_validation_blocks_missing_required_fields(): void
    {
        $response = $this->postJson(route('orderform.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_date', 'client_id', 'job_name', 'papers', 'printing_jobs']);
    }

    public function test_can_soft_delete_order_form(): void
    {
        $orderForm = OrderForm::create([
            'order_no' => 'VO25-26/00001',
            'order_date' => now()->toDateString(),
            'client_id' => $this->clientId,
            'job_name' => 'Delete test',
            'status' => 'Created',
            'year_id' => $this->yearId,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('orderform.destroy', $orderForm->id), [
            'id' => $orderForm->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status_code' => 200]);
        $this->assertSoftDeleted('order_forms', ['id' => $orderForm->id]);
    }

    public function test_user_without_permission_cannot_create(): void
    {
        $stranger = User::factory()->create();
        $this->createRoleWithPermissions('no-orderform', ['city-list'], $stranger);
        $this->actingAs($stranger);

        $response = $this->postJson(route('orderform.store'), [
            'order_date' => now()->toDateString(),
            'client_id' => $this->clientId,
            'job_name' => 'Should fail',
            'papers' => [['paper_id' => $this->paperId, 'qty' => 1]],
            'printing_jobs' => [[]],
        ]);

        $response->assertStatus(403);
    }
}
