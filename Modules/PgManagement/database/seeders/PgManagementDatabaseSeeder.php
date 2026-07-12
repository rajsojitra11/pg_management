<?php

namespace Modules\PgManagement\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\PgManagement\Models\PgManagement;
use Modules\User\Models\User;

class PgManagementDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $country = DB::table('countries')->where('code', 'IN')->first();
        $state = DB::table('states')->where('code', 'GJ')->where('country_id', $country?->id)->first();
        $city = DB::table('cities')->where('name', 'Rajkot')->where('country_id', $country?->id)->where('state_id', $state?->id)->first();

        $countryId = $country?->id;
        $stateId = $state?->id;
        $cityId = $city?->id;

        $pgAdminEmails = [
            'rajsojitra52@gmail.com',
            'rajs.techfirst@gmail.com',
            'mcae240046@gmail.com',
        ];

        $users = User::whereIn('email', $pgAdminEmails)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No Pg_Admin users found. Skipping PG Management seeding.');

            return;
        }

        $pgNames = [
            'Raj PG Home',
            'Raj PG Residency',
            'Raj PG Palace',
            'TechFirst PG House',
            'TechFirst PG Inn',
            'TechFirst PG Suites',
            'MCAE PG Comfort',
            'MCAE PG Living',
            'MCAE PG Stay',
        ];

        $index = 0;

        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $name = $pgNames[$index];

                $existing = PgManagement::where('pg_name', $name)->first();
                if ($existing) {
                    $index++;

                    continue;
                }

                PgManagement::create([
                    'pg_name' => $name,
                    'owner_id' => $user->id,
                    'mobile_no' => $user->mobile,
                    'total_block' => 2,
                    'total_room' => 10,
                    'country_id' => $countryId,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                    'address' => 'Sample Address, Rajkot',
                    'pincode' => '360001',
                    'status' => 'active',
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]);

                $index++;
            }
        }
    }
}
