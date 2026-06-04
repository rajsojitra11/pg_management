<?php

namespace Modules\Subscription\Database\Seeders;

use App\Traits\SeederLogging;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\Models\Subscription;

class SubscriptionDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $subscriptions = [
            [
                'subscriber_name' => 'Default Client',
                'email' => 'client@example.com',
                'phone' => '1234567890',
                'plan_type' => 'basic',
                'start_date' => $defaultDate,
                'end_date' => Carbon::parse($defaultDate)->addYear(),
                'status' => 'active',
                'amount' => 99.00,
                'payment_status' => 'paid',
            ],
        ];

        foreach ($subscriptions as $subscription) {
            $subscriptionRecord = Subscription::create(array_merge($subscription, [
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));

            DB::table('subscription_logs')
                ->where('subscription_id', $subscriptionRecord->id)
                ->where('activity', 'created')
                ->update([
                    'new_values' => json_encode($subscriptionRecord),
                    'user_agent' => 'System Data Creator',
                    'device' => 'Server',
                    'platform' => 'Server',
                    'browser' => 'Server',
                    'system_remark' => 'Initial Data Created By System Setup',
                    'user_remark' => 'subscription initial system configuration',
                ]);
        }

        $this->command->info('Subscription seeding completed successfully.');
    }
}
