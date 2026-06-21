<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\City\Database\Seeders\CityDatabaseSeeder;
use Modules\Country\Database\Seeders\CountryDatabaseSeeder;
use Modules\Currency\Database\Seeders\CurrencyDatabaseSeeder;
use Modules\EnvVariable\Database\Seeders\EnvVariableDatabaseSeeder;
use Modules\Login\Database\Seeders\LoginDatabaseSeeder;
use Modules\MenuMaster\Database\Seeders\MenuMasterDatabaseSeeder;
use Modules\Noticeboard\Models\Noticeboard;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Database\Seeders\PgManagementDatabaseSeeder;
use Modules\Role\Database\Seeders\RoleDatabaseSeeder;
use Modules\Room\Database\Seeders\RoomDatabaseSeeder;
use Modules\Setting\Database\Seeders\SettingDatabaseSeeder;
use Modules\State\Database\Seeders\StateDatabaseSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionDatabaseSeeder;
use Modules\Tenant\Models\Tenant;
use Modules\Unit\Database\Seeders\UnitDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
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
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->call([
            // Auth core
            UserDatabaseSeeder::class,
            RoleDatabaseSeeder::class,
            LoginDatabaseSeeder::class,
            MenuMasterDatabaseSeeder::class,
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
        ]);

        // ── Demo data: one record per remaining entity table ──────────────
        $this->seedDemoData();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

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
        $pgAdminUser = DB::table('users')->where('username', 'pg_admin')->first();
        $tenantUser = DB::table('users')->where('username', 'tenant')->first();

        if (! $pg || ! $room || ! $pgAdminUser || ! $tenantUser) {
            $this->command->warn('Required seed records not found. Skipping demo data creation.');

            return;
        }

        // ── Noticeboard ────────────────────────────────────────────
        $noticeboard = Noticeboard::create([
            'user_id' => $pgAdminUser->id,
            'pg_id' => $pg->id,
            'title' => 'Welcome to Default PG',
            'description' => 'Welcome to our PG accommodation. Please read the house rules and enjoy your stay.',
            'status' => 'active',
            'created_by' => $superAdminId,
            'updated_by' => $superAdminId,
            'created_at' => $migrationDate,
            'updated_at' => $migrationDate,
        ]);
        $this->command->info("Created noticeboard: {$noticeboard->title}");

        // ── Tenant ─────────────────────────────────────────────────
        $tenant = Tenant::create([
            'user_id' => $tenantUser->id,
            'name' => 'Demo Tenant',
            'email' => 'demo.tenant@example.com',
            'phone' => '9876543300',
            'address' => 'Room 101, Default PG, Sample Address',
            'status' => 'active',
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'bed_no' => 'A1',
            'date_of_birth' => '1995-06-15',
            'gender' => 'male',
            'occupation' => 'Software Engineer',
            'checkin_date' => '2025-01-01',
            'expected_checkout_date' => '2025-12-31',
            'monthly_rent' => 5000.00,
            'security_deposit' => 10000.00,
            'payment_method' => 'UPI',
            'id_proof_type' => 'Aadhar',
            'id_proof_number' => '1234-5678-9012',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_relation' => 'Father',
            'emergency_contact_number' => '9876543400',
            'permanent_address' => 'Sample Permanent Address, City',
            'additional_notes' => 'Demo tenant created during seeding.',
            'created_by' => $superAdminId,
            'updated_by' => $superAdminId,
            'created_at' => $migrationDate,
            'updated_at' => $migrationDate,
        ]);
        $this->command->info("Created tenant: {$tenant->name}");

        // ── Payment ─────────────────────────────────────────────────
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'pg_id' => $pg->id,
            'room_id' => $room->id,
            'payment_date' => '2025-01-01',
            'amount' => 5000.00,
            'payment_method' => 'UPI',
            'reference_no' => 'REF-DEMO-001',
            'remarks' => 'Demo payment for monthly rent',
            'status' => 'paid',
            'created_by' => $superAdminId,
            'updated_by' => $superAdminId,
            'created_at' => $migrationDate,
            'updated_at' => $migrationDate,
        ]);
        $this->command->info("Created payment: {$payment->reference_no}");
    }
}
