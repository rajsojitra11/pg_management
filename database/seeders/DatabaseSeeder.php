<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\City\Database\Seeders\CityDatabaseSeeder;
use Modules\Complaint\Database\Seeders\ComplaintDatabaseSeeder;
use Modules\Country\Database\Seeders\CountryDatabaseSeeder;
use Modules\Currency\Database\Seeders\CurrencyDatabaseSeeder;
use Modules\Email\Database\Seeders\EmailDatabaseSeeder;
use Modules\EnvVariable\Database\Seeders\EnvVariableDatabaseSeeder;
use Modules\Login\Database\Seeders\LoginDatabaseSeeder;
use Modules\Maintenance\Database\Seeders\MaintenanceDatabaseSeeder;
use Modules\MenuMaster\Database\Seeders\MenuMasterDatabaseSeeder;
use Modules\Noticeboard\Models\Noticeboard;
use Modules\Payment\Database\Seeders\PaymentDatabaseSeeder;
use Modules\PgManagement\Database\Seeders\PgManagementDatabaseSeeder;
use Modules\Role\Database\Seeders\RoleDatabaseSeeder;
use Modules\Room\Database\Seeders\RoomDatabaseSeeder;
use Modules\Service\Database\Seeders\ServiceDatabaseSeeder;
use Modules\Setting\Database\Seeders\SettingDatabaseSeeder;
use Modules\State\Database\Seeders\StateDatabaseSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionDatabaseSeeder;
use Modules\Tenant\Database\Seeders\TenantDatabaseSeeder;
use Modules\Unit\Database\Seeders\UnitDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
use Modules\User\Models\UserProfile;
use Modules\Year\Database\Seeders\YearDatabaseSeeder;
use Symfony\Component\Process\Process;

class DatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds.
     *
     * Order respects FK dependencies:
     *   1. Auth core       — users, roles, permissions, menus, env vars
     *   2. Lookups         — countries, states, cities, currencies, units, years
     *   3. Settings        — needs country/state/city references
     *   4. Subscriptions   — needs users
     *   5. PG & Room       — needs users, countries/states/cities
     *   6. Demo data       — one record per remaining entity with proper FKs
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            // Auth core
            UserDatabaseSeeder::class,
            RoleDatabaseSeeder::class,
            LoginDatabaseSeeder::class,
            MenuMasterDatabaseSeeder::class,
            EmailDatabaseSeeder::class,
            EnvVariableDatabaseSeeder::class,

            // Lookups — run before SettingSeeder (it queries IN/GJ/Rajkot)
            CountryDatabaseSeeder::class,
            StateDatabaseSeeder::class,
            CityDatabaseSeeder::class,
            CurrencyDatabaseSeeder::class,
            UnitDatabaseSeeder::class,
            YearDatabaseSeeder::class,

            // Settings — needs countries/states/cities from lookups above
            SettingDatabaseSeeder::class,

            // Subscriptions
            SubscriptionDatabaseSeeder::class,

            // PG & Rooms — needs users, countries/states/cities
            PgManagementDatabaseSeeder::class,
            RoomDatabaseSeeder::class,
            ServiceDatabaseSeeder::class,
            TenantDatabaseSeeder::class,
            PaymentDatabaseSeeder::class,
            ComplaintDatabaseSeeder::class,
            MaintenanceDatabaseSeeder::class,
        ]);

        // ── Demo data: one record per remaining entity table ──────────────
        $this->seedDemoData();

        Schema::enableForeignKeyConstraints();

        $this->command->info('Regenerating optimized autoload...');
        try {
            $process = new Process(['composer', 'dump-autoload', '-o']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(600);
            $process->run();
            if ($process->isSuccessful()) {
                $this->command->info('Autoload regenerated successfully.');
            } else {
                $this->command->warn('Autoload regeneration failed: '.$process->getErrorOutput());
            }
        } catch (\Throwable $e) {
            $this->command->warn('Autoload regeneration skipped: '.$e->getMessage());
            $this->command->warn('If needed, run "composer dump-autoload -o" manually.');
        }
    }

    /**
     * Create one record per entity table that doesn't have its own seeder,
     * with proper FK relationships to previously seeded data.
     */
    private function seedDemoData(): void
    {
        $migrationDate = $this->getMigrationDate();
        $superAdminId = $this->getSuperAdminId();

        // Look up records seeded by sub-seeders
        $pg = DB::table('pg_management')->first();
        $room = DB::table('pg_rooms')->first();
        $superAdminUser = DB::table('users')->where('username', 'super_admin')->first();

        if (! $pg || ! $room || ! $superAdminUser) {
            $this->command->warn('Required seed records not found. Skipping demo data creation.');

            return;
        }

        // ── Noticeboard ────────────────────────────────────────────
        $noticeboard = Noticeboard::create([
            'user_id' => $superAdminUser->id,
            'pg_id' => $pg->id,
            'title' => 'Welcome to Default PG',
            'description' => 'Welcome to our PG accommodation. Please read the house rules and enjoy your stay.',
            'status' => 'active',
            'created_by' => $superAdminId,
            'updated_by' => $superAdminId,
            'created_at' => $migrationDate,
            'updated_at' => $migrationDate,
        ]);
        // ── Set state/city for Pg_Admin users ──────────────────────────
        $country = DB::table('countries')->where('code', 'IN')->first();
        $state = DB::table('states')->where('code', 'GJ')->where('country_id', $country?->id)->first();
        $city = DB::table('cities')->where('name', 'Rajkot')->where('country_id', $country?->id)->where('state_id', $state?->id)->first();

        if ($country && $state && $city) {
            $pgAdminUserIds = DB::table('users')
                ->whereIn('username', ['rajsojitra52', 'rajs.techfirst', 'mcae240046'])
                ->pluck('id');

            UserProfile::whereIn('user_id', $pgAdminUserIds)->update([
                'state_id' => $state->id,
                'city_id' => $city->id,
            ]);

        }
    }
}
