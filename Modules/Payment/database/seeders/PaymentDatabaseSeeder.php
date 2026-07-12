<?php

namespace Modules\Payment\Database\Seeders;

use App\Traits\SeederLogging;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Payment\Models\Payment;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class PaymentDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $tenants = Tenant::inRandomOrder()->limit(100)->get();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Skipping Payment seeding.');

            return;
        }

        $paymentMethods = ['UPI', 'Cash', 'Bank Transfer', 'Cheque', 'Card'];
        $statuses = ['paid', 'paid', 'paid', 'paid', 'pending', 'verified'];
        $remarks = [
            'Monthly rent payment',
            'Rent for current month',
            'Advance rent payment',
            'Room rent paid',
            'Monthly dues cleared',
        ];

        $index = 0;

        foreach ($tenants as $tenant) {
            $index++;
            $paymentDate = Carbon::parse($defaultDate)->addDays($index % 30);
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
            $verified = $statuses[array_rand($statuses)];
            $remark = $remarks[array_rand($remarks)];

            $existing = Payment::where('tenant_id', $tenant->id)
                ->where('payment_date', $paymentDate->toDateString())
                ->first();

            if ($existing) {
                continue;
            }

            Payment::create([
                'tenant_id' => $tenant->id,
                'pg_id' => $tenant->pg_id,
                'room_id' => $tenant->room_id,
                'payment_date' => $paymentDate->toDateString(),
                'amount' => $tenant->monthly_rent,
                'payment_method' => $paymentMethod,
                'reference_no' => 'PAY-DEMO-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'remarks' => $remark,
                'verified' => $verified,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]);
        }
    }
}
