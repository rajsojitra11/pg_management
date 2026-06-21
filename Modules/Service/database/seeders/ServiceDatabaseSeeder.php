<?php

namespace Modules\Service\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;

class ServiceDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        // Default categories
        $categories = [
            ['service_category_name' => 'Cleaning'],
            ['service_category_name' => 'Maintenance'],
        ];

        foreach ($categories as $cat) {
            ServiceCategory::create(array_merge($cat, [
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));
        }

        // Default services
        $services = [
            ['service_category_id' => 1, 'service_name' => 'Room Cleaning'],
            ['service_category_id' => 1, 'service_name' => 'Laundry'],
            ['service_category_id' => 2, 'service_name' => 'Electrical Repair'],
            ['service_category_id' => 2, 'service_name' => 'Plumbing'],
        ];

        foreach ($services as $service) {
            Service::create(array_merge($service, [
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));
        }

        $this->command->info('Service seeding completed successfully.');
    }
}
