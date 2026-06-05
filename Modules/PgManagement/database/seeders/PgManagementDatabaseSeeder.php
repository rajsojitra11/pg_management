<?php

namespace Modules\PgManagement\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Modules\PgManagement\Models\PgManagement;

class PgManagementDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $pgRecords = [
            [
                'pg_name' => 'Default PG',
                'mobile_no' => '1234567890',
                'total_block' => 2,
                'total_room' => 20,
                'address' => 'Default Address',
                'pincode' => '123456',
            ],
        ];

        foreach ($pgRecords as $record) {
            PgManagement::create(array_merge($record, [
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));
        }

        $this->command->info('PG Management seeding completed successfully.');
    }
}
