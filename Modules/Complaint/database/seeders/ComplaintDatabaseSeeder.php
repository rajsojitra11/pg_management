<?php

namespace Modules\Complaint\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;

class ComplaintDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $this->command->info('Complaint seeding completed successfully.');
    }
}
