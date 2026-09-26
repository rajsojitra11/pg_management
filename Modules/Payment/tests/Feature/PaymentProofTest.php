<?php

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected PgManagement $pg;

    protected Room $room;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->createRoleWithPermissions('PaymentProof_Test', [
            'mobile-payment-list',
            'mobile-payment-create',
            'mobile-payment-view',
            'mobile-payment-edit',
            'mobile-payment-delete',
        ], $this->user);
        $this->actingAs($this->user);
        Sanctum::actingAs($this->user);

        $this->pg = PgManagement::create(['pg_name' => 'Test PG', 'owner_id' => $this->user->id]);
        $this->room = Room::create(['pg_id' => $this->pg->id, 'category_id' => 1, 'room_no' => 'A-101']);
        $this->tenant = Tenant::create([
            'pg_id' => $this->pg->id,
            'room_id' => $this->room->id,
            'name' => 'Proof Tenant',
            'monthly_rent' => 5000,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => $this->tenant->id,
            'pg_id' => $this->pg->id,
            'room_id' => $this->room->id,
            'payment_date' => now()->toDateString(),
            'amount' => 5000,
            'payment_method' => 'UPI',
        ], $overrides);
    }

    public function test_show_returns_the_payment_proof_url(): void
    {
        $payment = Payment::create($this->payload([
            'payment_proof' => 'payment-proofs/receipt.png',
            'verified' => 'pending',
        ]));

        $response = $this->getJson('/api/v1/payments/'.$payment->id);

        $response->assertOk()
            ->assertJsonPath('data.payment_proof', url('/storage/payment-proofs/receipt.png'));
    }

    public function test_show_returns_null_proof_when_not_uploaded(): void
    {
        $payment = Payment::create($this->payload());

        $this->getJson('/api/v1/payments/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('data.payment_proof', null);
    }

    public function test_store_persists_the_uploaded_proof_and_returns_its_url(): void
    {
        $response = $this->postJson('/api/v1/payments', $this->payload([
            'payment_proof' => UploadedFile::fake()->image('receipt.png'),
        ]));

        $response->assertCreated();

        $proof = $response->json('data.payment_proof');
        $this->assertNotNull($proof);
        $this->assertStringContainsString('/storage/payment-proofs/', $proof);
        $this->assertStringEndsWith('.png', $proof);

        $payment = Payment::firstOrFail();
        $this->assertNotNull($payment->payment_proof);
        Storage::disk('public')->assertExists($payment->payment_proof);
    }

    public function test_update_replaces_the_previous_proof_file(): void
    {
        Storage::disk('public')->put('payment-proofs/old.png', 'old');
        $payment = Payment::create($this->payload([
            'payment_proof' => 'payment-proofs/old.png',
        ]));

        $this->putJson('/api/v1/payments/'.$payment->id, $this->payload([
            'payment_proof' => UploadedFile::fake()->image('new.png'),
        ]))->assertOk();

        $payment->refresh();
        $this->assertNotSame('payment-proofs/old.png', $payment->payment_proof);
        Storage::disk('public')->assertMissing('payment-proofs/old.png');
        Storage::disk('public')->assertExists($payment->payment_proof);
    }

    public function test_destroy_removes_the_proof_file(): void
    {
        Storage::disk('public')->put('payment-proofs/remove-me.png', 'bytes');
        $payment = Payment::create($this->payload([
            'payment_proof' => 'payment-proofs/remove-me.png',
        ]));

        $this->deleteJson('/api/v1/payments/'.$payment->id)->assertNoContent();

        Storage::disk('public')->assertMissing('payment-proofs/remove-me.png');
    }

    public function test_index_includes_the_payment_proof_url(): void
    {
        Payment::create($this->payload([
            'payment_proof' => 'payment-proofs/listed.png',
        ]));

        $this->getJson('/api/v1/payments')
            ->assertOk()
            ->assertJsonPath('data.0.payment_proof', url('/storage/payment-proofs/listed.png'));
    }
}
