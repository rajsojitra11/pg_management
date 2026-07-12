<?php

namespace Modules\Room\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomCategory;
use Modules\User\Models\User;

class RoomDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $pgs = PgManagement::all();

        if ($pgs->isEmpty()) {
            $this->command->warn('No PG records found. Skipping Room seeding.');

            return;
        }

        $categoryTemplates = [
            ['category_name' => 'Two Sharing', 'bed_capacity' => 2, 'rent_amount' => 5000],
            ['category_name' => 'Three Sharing', 'bed_capacity' => 3, 'rent_amount' => 4000],
            ['category_name' => 'Four Sharing', 'bed_capacity' => 4, 'rent_amount' => 3000],
        ];

        $roomNo = 101;

        foreach ($pgs as $pg) {
            foreach ($categoryTemplates as $template) {
                $catName = $template['category_name'];

                $existing = RoomCategory::where('pg_id', $pg->id)
                    ->where('category_name', $catName)
                    ->first();

                if ($existing) {
                    continue;
                }

                $category = RoomCategory::create([
                    'pg_id' => $pg->id,
                    'category_name' => $catName,
                    'status' => 'active',
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]);

                for ($i = 0; $i < 3; $i++) {
                    Room::create([
                        'pg_id' => $pg->id,
                        'category_id' => $category->id,
                        'room_no' => (string) $roomNo,
                        'bed_capacity' => $template['bed_capacity'],
                        'rent_amount' => $template['rent_amount'],
                        'status' => 'active',
                        'created_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'created_at' => $defaultDate,
                        'updated_at' => $defaultDate,
                    ]);

                    $roomNo++;
                }
            }
        }
    }
}
