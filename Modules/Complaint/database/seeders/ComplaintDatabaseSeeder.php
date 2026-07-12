<?php

namespace Modules\Complaint\Database\Seeders;

use App\Traits\SeederLogging;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Complaint\Models\Complaint;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Service\Models\Service;
use Modules\User\Models\User;

class ComplaintDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $pgs = PgManagement::all();

        if ($pgs->isEmpty()) {
            $this->command->warn('No PG records found. Skipping Complaint seeding.');

            return;
        }

        $services = Service::all();

        if ($services->isEmpty()) {
            $this->command->warn('No services found. Skipping Complaint seeding.');

            return;
        }

        $noteOptions = [
            'Fan not working properly, needs repair',
            'Tap is leaking, please fix',
            'AC not cooling, needs servicing',
            'Bed frame is broken',
            'Room needs deep cleaning',
            'WiFi router not working',
            'Fridge making noise',
            'Door lock is jammed',
            'Cockroach infestation in room',
            'Paint is peeling off the walls',
            'Power socket not working',
            'Water pressure is very low',
            'Toilet flush is broken',
            'Cupboard door hinge broken',
            'Garbage not collected for days',
            'Internet very slow since yesterday',
            'Washing machine not spinning',
            'Main gate lock needs replacement',
            'Mosquito problem in common area',
            'Food quality has declined',
        ];

        $statuses = ['pending', 'in_progress', 'resolved', 'resolved', 'pending'];

        $lastComplaint = Complaint::orderByDesc('id')->value('complaint_no');
        $year = Carbon::parse($defaultDate)->format('Y');
        $seq = $lastComplaint ? (int) substr($lastComplaint, 4) + 1 : 1;

        foreach ($pgs as $pg) {
            $rooms = Room::where('pg_id', $pg->id)->get();

            if ($rooms->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < 5; $i++) {
                $room = $rooms->random();
                $service = $services->random();
                $note = $noteOptions[array_rand($noteOptions)];
                $status = $statuses[array_rand($statuses)];
                $complaintDate = Carbon::parse($defaultDate)->addDays(rand(0, 60));

                $complaintNo = $year.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $seq++;

                Complaint::create([
                    'complaint_no' => $complaintNo,
                    'pg_id' => $pg->id,
                    'room_id' => $room->id,
                    'service_category_id' => $service->service_category_id,
                    'service_id' => $service->id,
                    'complaint_date' => $complaintDate->toDateString(),
                    'note' => $note,
                    'status' => $status,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]);
            }
        }
    }
}
