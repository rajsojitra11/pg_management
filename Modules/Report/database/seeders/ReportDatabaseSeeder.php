<?php

namespace Modules\Report\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Modules\User\Models\User;

class ReportDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $systemAdmin = User::where('username', 'super_admin')->first();

        if (! $systemAdmin) {
            $this->command->warn('No super_admin user found. Run UserDatabaseSeeder first. Skipping Report seeding.');

            return;
        }

        // Reports are read-only views; no transactional data to seed.
        // Permissions (report-list etc.) come from PermissionTableSeeder and
        // the nav menu from MenuMasterDatabaseSeeder.
        $this->command->info('Report module seeded (read-only; no data inserts needed).');
    }
}
