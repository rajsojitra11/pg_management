<?php

namespace Modules\Unit\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Unit\Models\Unit;

class UnitDatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $units = ['GM', 'KG', 'Ltr', 'ML', 'Box', 'Pcs'];

        foreach ($units as $name) {
            $unit = Unit::create([
                'name' => $name,
                'unit_value' => '1',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]);

            // The HasActivityLogging trait auto-creates a log row. Rewrite it
            // to use the seeder system-remark so initial seeded data is
            // distinguishable from user-created data in the audit trail.
            DB::table('unit_logs')
                ->where('unit_id', $unit->id)
                ->where('activity', 'created')
                ->update([
                    'new_values' => json_encode($unit),
                    'user_agent' => 'System Data Creator',
                    'device' => 'Server',
                    'platform' => 'Server',
                    'browser' => 'Server',
                    'system_remark' => 'Initial Data Created By System Setup',
                    'user_remark' => 'unit initial system configuration',
                ]);
        }
    }
}
