<?php

namespace Modules\Noticeboard\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;

class NoticeboardDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        // Default data can be seeded here
        $this->command->info('Noticeboard seeding completed successfully.');
    }
}
