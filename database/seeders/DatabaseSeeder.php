<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\City\Database\Seeders\CityDatabaseSeeder;
use Modules\Country\Database\Seeders\CountryDatabaseSeeder;
use Modules\Currency\Database\Seeders\CurrencyDatabaseSeeder;
use Modules\Dashbord\Database\Seeders\DashbordDatabaseSeeder;
use Modules\EnvVariable\Database\Seeders\EnvVariableDatabaseSeeder;
use Modules\Login\Database\Seeders\LoginDatabaseSeeder;
use Modules\MenuMaster\Database\Seeders\MenuMasterDatabaseSeeder;
use Modules\Noticeboard\Database\Seeders\NoticeboardDatabaseSeeder;
use Modules\Role\Database\Seeders\RoleDatabaseSeeder;
use Modules\Setting\Database\Seeders\SettingDatabaseSeeder;
use Modules\State\Database\Seeders\StateDatabaseSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionDatabaseSeeder;
use Modules\Tenant\Database\Seeders\TenantDatabaseSeeder;
use Modules\Unit\Database\Seeders\UnitDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
use Modules\Year\Database\Seeders\YearDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Order:
     *   1. Auth core — users, roles, permissions, menus, settings, env vars
     *   2. Lookups   — countries, states, cities, currencies, units, years
     */
    public function run(): void
    {
        // Disable FK checks for seeding — seeders reference user ID 1 which may not
        // match the auto-increment value after migrate:fresh on MariaDB/MySQL.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->call([
            // Auth core
            UserDatabaseSeeder::class,
            RoleDatabaseSeeder::class,
            LoginDatabaseSeeder::class,
            MenuMasterDatabaseSeeder::class,
            SettingDatabaseSeeder::class,
            EnvVariableDatabaseSeeder::class,
            // DashbordDatabaseSeeder::class,

            // Lookups
            CountryDatabaseSeeder::class,
            StateDatabaseSeeder::class,
            CityDatabaseSeeder::class,
            CurrencyDatabaseSeeder::class,
            SubscriptionDatabaseSeeder::class,
            NoticeboardDatabaseSeeder::class,
            TenantDatabaseSeeder::class,
            UnitDatabaseSeeder::class,
            YearDatabaseSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Regenerate optimized autoload after seeding (best-effort — the data is
        // already committed, so a slow/missing composer must not fail the seed).
        $this->command->info('Regenerating optimized autoload...');
        try {
            $process = new Process(['composer', 'dump-autoload', '-o']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(600);
            $process->run();
            if ($process->isSuccessful()) {
                $this->command->info('Autoload regenerated successfully.');
            } else {
                $this->command->warn('Autoload regeneration failed: ' . $process->getErrorOutput());
            }
        } catch (\Throwable $e) {
            $this->command->warn('Autoload regeneration skipped: ' . $e->getMessage());
            $this->command->warn('If needed, run "composer dump-autoload -o" manually.');
        }
    }
}
