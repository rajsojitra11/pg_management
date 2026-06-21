<?php

namespace Modules\User\Database\Seeders;

use App\Traits\SeederLogging;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Modules\User\Models\UserProfile;

/**
 * Seeds the two baseline roles + their admin users.
 *
 * - Super_Admin: full access to everything (permissions synced by PermissionTableSeeder).
 * - Pg_Admin: all permissions except `system-administration-access`.
 *
 * Add more roles + users here as your project grows. The permission catalog
 * is OWNED BY PermissionTableSeeder (runs right after this).
 */
class CreateAdminUserSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $existingCount = DB::table('users')->count();
        $migrationDate = $this->getMigrationDate();

        if ($existingCount > 0) {
            // Default to true (truncate) for non-interactive mode (e.g. migrate:fresh --seed)
            if ($this->command?->confirm('Do you want to truncate the users table first?', true) ?? true) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('user_profile')->truncate();
                DB::table('users')->truncate();
                DB::table('roles')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        // ── Super_Admin user + role (web only) ──────────────────────────
        $superAdminUser = $this->createUserWithLogging([
            'name' => 'Super_Admin',
            'mobile' => '9876543210',
            'username' => 'super_admin',
            'email' => 'super@adm.com',
            'password' => bcrypt('Tech@#311'),
            'status' => 'Active',
        ], [
            'firstname' => 'Super',
            'lastname' => 'Admin',
            'date_of_birth' => '1990-01-01',
        ]);

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super_Admin', 'guard_name' => 'web'],
            [
                'title' => 'Super_Admin',
                'access_type' => 'web',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        if ($superAdminUser && $superAdminRole) {
            $superAdminUser->assignRole($superAdminRole);
            $this->logSeederOperation("Assigned Super_Admin role to user: {$superAdminUser->name}", User::class, $superAdminRole);
        }

        // ── Pg_Admin user + role (web + mobile app) ──────────────────
        $companyAdminUser = $this->createUserWithLogging([
            'name' => 'Pg Admin',
            'mobile' => '9876543110',
            'username' => 'pg_admin',
            'email' => 'pg_admin@adm.com',
            'password' => bcrypt('Company'),
            'status' => 'Active',
        ], [
            'firstname' => 'Pg',
            'lastname' => 'Admin',
            'date_of_birth' => '1990-01-01',
        ]);

        $companyAdminRole = Role::firstOrCreate(
            ['name' => 'Pg_Admin', 'guard_name' => 'web'],
            [
                'title' => 'Pg_Admin',
                'access_type' => 'both',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        if ($companyAdminUser && $companyAdminRole) {
            $companyAdminUser->assignRole($companyAdminRole);
            $this->logSeederOperation("Assigned Pg_Admin role to user: {$companyAdminUser->name}", User::class, $companyAdminRole);
        }

        // ── Pg_Manager user + role (mobile app only) ─────────────────
        $pgManagerUser = $this->createUserWithLogging([
            'name' => 'Pg Manager',
            'mobile' => '9876543220',
            'username' => 'pg_manager',
            'email' => 'pg_manager@app.com',
            'password' => bcrypt('Manager@123'),
            'status' => 'Active',
        ], [
            'firstname' => 'Pg',
            'lastname' => 'Manager',
            'date_of_birth' => '1990-01-01',
        ]);

        $pgManagerRole = Role::firstOrCreate(
            ['name' => 'Pg_Manager', 'guard_name' => 'web'],
            [
                'title' => 'Pg_Manager',
                'access_type' => 'mobile',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        if ($pgManagerUser && $pgManagerRole) {
            $pgManagerUser->assignRole($pgManagerRole);
            $this->logSeederOperation("Assigned Pg_Manager role to user: {$pgManagerUser->name}", User::class, $pgManagerRole);
        }

        // ── Tenant user + role (mobile app only) ─────────────────────
        $tenantUser = $this->createUserWithLogging([
            'name' => 'Tenant',
            'mobile' => '9876543230',
            'username' => 'tenant',
            'email' => 'tenant@app.com',
            'password' => bcrypt('Tenant@123'),
            'status' => 'Active',
        ], [
            'firstname' => 'Tenant',
            'lastname' => 'User',
            'date_of_birth' => '1990-01-01',
        ]);

        $tenantRole = Role::firstOrCreate(
            ['name' => 'Tenant', 'guard_name' => 'web'],
            [
                'title' => 'Tenant',
                'access_type' => 'mobile',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        if ($tenantUser && $tenantRole) {
            $tenantUser->assignRole($tenantRole);
            $this->logSeederOperation("Assigned Tenant role to user: {$tenantUser->name}", User::class, $tenantRole);
        }
    }

    /**
     * Create user + sibling user_profile row. Idempotent — skips if a user
     * with the same email or username already exists.
     */
    private function createUserWithLogging(array $userData, array $profileData): ?User
    {
        $existingUser = User::where('email', $userData['email'])
            ->orWhere('username', $userData['username'])
            ->first();

        if ($existingUser) {
            $this->command->info("User {$userData['name']} already exists. Skipping creation.");

            return $existingUser;
        }

        try {
            $migrationDate = $this->getMigrationDate();

            $user = User::create(array_merge($userData, [
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]));

            if ($user) {
                $this->stampInitialDataLoad($user, Carbon::parse($migrationDate));

                UserProfile::create(array_merge($profileData, [
                    'user_id' => $user->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_at' => $migrationDate,
                    'updated_at' => $migrationDate,
                ]));
            }

            $this->logSeederOperation("Created user: {$userData['name']} with profile", User::class, $user);
            $this->command->info("User {$userData['name']} created successfully.");

            return $user;
        } catch (Exception $e) {
            $this->command->error("Error creating user {$userData['name']}: ".$e->getMessage());

            return null;
        }
    }
}
