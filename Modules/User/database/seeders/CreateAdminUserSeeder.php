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
                $this->safeTruncate('user_profile');
                $this->safeTruncate('users');
                $this->safeTruncate('roles');
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
                'created_by' => $superAdminUser?->id,
                'updated_by' => $superAdminUser?->id,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        if ($superAdminUser && $superAdminRole) {
            $superAdminUser->assignRole($superAdminRole);
            $this->logSeederOperation("Assigned Super_Admin role to user: {$superAdminUser->name}", User::class, $superAdminRole);
        }

        // ── Pg_Admin role (no default user) ──────────────────────────────
        $pgAdminRole = Role::firstOrCreate(
            ['name' => 'Pg_Admin', 'guard_name' => 'web'],
            [
                'title' => 'Pg_Admin',
                'access_type' => 'both',
                'created_by' => $superAdminUser?->id,
                'updated_by' => $superAdminUser?->id,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        // ── Tenant role (no default user) ─────────────────────────────────
        $tenantRole = Role::firstOrCreate(
            ['name' => 'Tenant', 'guard_name' => 'web'],
            [
                'title' => 'Tenant',
                'access_type' => 'mobile',
                'created_by' => $superAdminUser?->id,
                'updated_by' => $superAdminUser?->id,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        // ── Pg_Manager role (no default user) ─────────────────────────────
        $pgManagerRole = Role::firstOrCreate(
            ['name' => 'Pg_Manager', 'guard_name' => 'web'],
            [
                'title' => 'Pg_Manager',
                'access_type' => 'both',
                'created_by' => $superAdminUser?->id,
                'updated_by' => $superAdminUser?->id,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]
        );

        // ── Pg_Admin users ───────────────────────────────────────────────
        $pgAdminUsers = [
            [
                'name' => 'Raj Sojitra',
                'mobile' => '9876543311',
                'username' => 'rajsojitra52',
                'email' => 'rajsojitra52@gmail.com',
                'password' => bcrypt('PgAdmin@123'),
                'status' => 'Active',
                'profile' => [
                    'firstname' => 'Raj',
                    'lastname' => 'Sojitra',
                    'date_of_birth' => '1990-01-01',
                ],
            ],
            [
                'name' => 'Raj Techfirst',
                'mobile' => '9876543322',
                'username' => 'rajs.techfirst',
                'email' => 'rajs.techfirst@gmail.com',
                'password' => bcrypt('PgAdmin@123'),
                'status' => 'Active',
                'profile' => [
                    'firstname' => 'Raj',
                    'lastname' => 'Techfirst',
                    'date_of_birth' => '1990-01-01',
                ],
            ],
            [
                'name' => 'MCAE 240046',
                'mobile' => '9876543333',
                'username' => 'mcae240046',
                'email' => 'mcae240046@gmail.com',
                'password' => bcrypt('PgAdmin@123'),
                'status' => 'Active',
                'profile' => [
                    'firstname' => 'MCAE',
                    'lastname' => '240046',
                    'date_of_birth' => '1990-01-01',
                ],
            ],
        ];

        foreach ($pgAdminUsers as $userData) {
            $profileData = $userData['profile'];
            unset($userData['profile']);

            $user = $this->createUserWithLogging($userData, $profileData, $superAdminUser?->id);

            if ($user && $pgAdminRole) {
                $user->assignRole($pgAdminRole);
                $this->logSeederOperation("Assigned Pg_Admin role to user: {$user->name}", User::class, $pgAdminRole);
            }
        }

    }

    /**
     * Create user + sibling user_profile row. Idempotent — skips if a user
     * with the same email or username already exists.
     */
    private function createUserWithLogging(array $userData, array $profileData, ?int $createdBy = null): ?User
    {
        $existingUser = User::where('email', $userData['email'])
            ->orWhere('username', $userData['username'])
            ->first();

        if ($existingUser) {
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

                $auditBy = $createdBy ?? $user->id;

                $user->update([
                    'created_by' => $auditBy,
                    'updated_by' => $auditBy,
                ]);

                UserProfile::create(array_merge($profileData, [
                    'user_id' => $user->id,
                    'parent_id' => $auditBy,
                    'created_by' => $auditBy,
                    'updated_by' => $auditBy,
                    'created_at' => $migrationDate,
                    'updated_at' => $migrationDate,
                ]));
            }

            $this->logSeederOperation("Created user: {$userData['name']} with profile", User::class, $user);

            return $user;
        } catch (Exception $e) {
            $this->command->error("Error creating user {$userData['name']}: ".$e->getMessage());

            return null;
        }
    }
}
