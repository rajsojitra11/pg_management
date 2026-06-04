<?php

namespace Modules\User\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin user and the project's permission catalog.
 *
 * The wider module-by-module seeding (lookups, env vars, menus, items)
 * runs from the root Database\Seeders\DatabaseSeeder. Keep this seeder
 * focused on user + permissions so the order stays explicit.
 */
class UserDatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds for the User module.
     */
    public function run(): void
    {
        $this->call([
            CreateAdminUserSeeder::class,
            PermissionTableSeeder::class,
        ]);
    }
}
