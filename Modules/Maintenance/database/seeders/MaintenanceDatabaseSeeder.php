<?php

namespace Modules\Maintenance\Database\Seeders;

use App\Traits\SeederLogging;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Complaint\Models\Complaint;
use Modules\Maintenance\Models\Maintenance;
use Modules\PgManagement\Models\PgManagement;
use Modules\User\Models\User;

class MaintenanceDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $pgs = PgManagement::all();

        if ($pgs->isEmpty()) {
            $this->command->warn('No PG records found. Skipping Maintenance seeding.');

            return;
        }

        $maintenanceYear = Carbon::parse($defaultDate)->format('Y');
        $lastMaintenance = Maintenance::orderByDesc('id')->value('maintenance_no');
        $seq = $lastMaintenance ? (int) substr($lastMaintenance, 8) + 1 : 1;

        foreach ($pgs as $pg) {
            $complaints = Complaint::where('pg_id', $pg->id)->inRandomOrder()->limit(3)->get();

            foreach ($complaints as $complaint) {
                $maintenanceNo = 'MNT-' . $maintenanceYear . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $seq++;

                Maintenance::create([
                    'maintenance_no' => $maintenanceNo,
                    'complaint_id' => $complaint->id,
                    'cost' => rand(500, 5000),
                    'description' => 'Maintenance completed for complaint ' . $complaint->complaint_no . ': ' . $complaint->note,
                    'maintenance_date' => Carbon::parse($defaultDate)->addDays(rand(1, 15))->toDateString(),
                    'status' => 'completed',
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]);
            }
        }
    }
}
