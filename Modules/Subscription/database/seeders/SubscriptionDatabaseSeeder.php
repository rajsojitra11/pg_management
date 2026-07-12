<?php

namespace Modules\Subscription\Database\Seeders;

use App\Traits\SeederLogging;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\Models\Subscription;
use Modules\User\Models\User;

class SubscriptionDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();
        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $pgAdminEmails = [
            'rajsojitra52@gmail.com',
            'rajs.techfirst@gmail.com',
            'mcae240046@gmail.com',
        ];

        $users = User::whereIn('email', $pgAdminEmails)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No Pg_Admin users found. Skipping subscription seeding.');

            return;
        }

        foreach ($users as $user) {
            $existing = Subscription::where('email', $user->email)->first();
            if ($existing) {
                continue;
            }

            $subscription = Subscription::create([
                'subscriber_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->mobile,
                'plan_type' => 'basic',
                'start_date' => $defaultDate,
                'end_date' => Carbon::parse('2030-12-31'),
                'status' => 'active',
                'amount' => 99.00,
                'payment_status' => 'paid',
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]);

            DB::table('subscription_logs')
                ->where('subscription_id', $subscription->id)
                ->where('activity', 'created')
                ->update([
                    'new_values' => json_encode($subscription),
                    'user_agent' => 'System Data Creator',
                    'device' => 'Server',
                    'platform' => 'Server',
                    'browser' => 'Server',
                    'system_remark' => 'Initial Data Created By System Setup',
                ]);
        }
    }
}
