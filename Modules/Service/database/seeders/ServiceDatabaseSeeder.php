<?php

namespace Modules\Service\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\User\Models\User;

class ServiceDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $categories = [
            [
                'service_category_name' => 'Electrical',
                'services' => [
                    'Fan not working',
                    'Light/bulb fused',
                    'Switch board issue',
                    'Power socket not working',
                    'Short circuit / sparking',
                    'MCB tripping',
                    'Wiring issue',
                ],
            ],
            [
                'service_category_name' => 'Plumbing',
                'services' => [
                    'Tap leakage',
                    'Water not coming',
                    'Low water pressure',
                    'Toilet flush not working',
                    'Bathroom drainage blocked',
                    'Geyser not heating water',
                    'Pipe leakage',
                ],
            ],
            [
                'service_category_name' => 'AC / Cooling',
                'services' => [
                    'AC not cooling',
                    'AC gas refill',
                    'AC servicing request',
                    'AC water leakage',
                    'Cooler not working',
                    'Fan speed issue',
                ],
            ],
            [
                'service_category_name' => 'Furniture & Fittings',
                'services' => [
                    'Bed/cot broken',
                    'Cupboard door/lock issue',
                    'Chair/table repair',
                    'Mattress replacement',
                    'Curtain rod fixing',
                    'Door/window handle broken',
                ],
            ],
            [
                'service_category_name' => 'Housekeeping / Cleaning',
                'services' => [
                    'Room cleaning request',
                    'Washroom cleaning',
                    'Garbage not collected',
                    'Common area cleaning',
                    'Pest issue in room',
                ],
            ],
            [
                'service_category_name' => 'Internet / WiFi',
                'services' => [
                    'WiFi not working',
                    'Slow internet speed',
                    'Router restart request',
                    'New device connection issue',
                ],
            ],
            [
                'service_category_name' => 'Appliances',
                'services' => [
                    'Fridge not cooling',
                    'Washing machine not working',
                    'Microwave issue',
                    'TV not working',
                    'Water purifier (RO) issue',
                ],
            ],
            [
                'service_category_name' => 'Security',
                'services' => [
                    'Room lock/key issue',
                    'CCTV not working',
                    'Main gate lock issue',
                    'Biometric/access card issue',
                    'Suspicious activity report',
                ],
            ],
            [
                'service_category_name' => 'Pest Control',
                'services' => [
                    'Cockroach infestation',
                    'Mosquito problem',
                    'Rat/rodent issue',
                    'Bed bugs complaint',
                ],
            ],
            [
                'service_category_name' => 'Food / Mess',
                'services' => [
                    'Food quality complaint',
                    'Mess timing issue',
                    'Hygiene complaint',
                    'Menu change request',
                ],
            ],
            [
                'service_category_name' => 'General / Others',
                'services' => [
                    'Painting request',
                    'Noise complaint',
                    'Parking issue',
                    'Other maintenance request',
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $category = ServiceCategory::firstOrCreate(
                ['service_category_name' => $catData['service_category_name']],
                [
                    'status' => 'active',
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]
            );

            foreach ($catData['services'] as $serviceName) {
                Service::firstOrCreate(
                    ['service_name' => $serviceName, 'service_category_id' => $category->id],
                    [
                        'status' => 'active',
                        'created_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'created_at' => $defaultDate,
                        'updated_at' => $defaultDate,
                    ]
                );
            }
        }
    }
}
