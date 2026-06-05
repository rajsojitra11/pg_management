<?php

namespace Modules\Room\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Modules\Room\Models\RoomCategory;
use Modules\Room\Models\Room;

class RoomDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        // Default categories
        $categories = [
            ['pg_id' => 1, 'category_name' => 'Standard Room'],
            ['pg_id' => 1, 'category_name' => 'Deluxe Room'],
        ];

        foreach ($categories as $cat) {
            RoomCategory::create(array_merge($cat, [
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));
        }

        // Default rooms
        $rooms = [
            ['pg_id' => 1, 'category_id' => 1, 'room_no' => '101', 'bed_capacity' => 2, 'rent_amount' => 5000],
            ['pg_id' => 1, 'category_id' => 1, 'room_no' => '102', 'bed_capacity' => 3, 'rent_amount' => 6000],
            ['pg_id' => 1, 'category_id' => 2, 'room_no' => '201', 'bed_capacity' => 2, 'rent_amount' => 8000],
        ];

        foreach ($rooms as $room) {
            Room::create(array_merge($room, [
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));
        }

        $this->command->info('Room seeding completed successfully.');
    }
}
