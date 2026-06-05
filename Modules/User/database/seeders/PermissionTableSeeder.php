<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $permissions = [
            // Administration
            [
                'title_tag' => 'Role',
                'title' => 'List',
                'name' => 'role-list',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Role',
                'title' => 'Create',
                'name' => 'role-create',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Role',
                'title' => 'Edit',
                'name' => 'role-edit',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Role',
                'title' => 'Delete',
                'name' => 'role-delete',
                'section' => 'Administration',
            ],

            [
                'title_tag' => 'User',
                'title' => 'List',
                'name' => 'users-list',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'User',
                'title' => 'Create',
                'name' => 'users-create',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'User',
                'title' => 'Edit',
                'name' => 'users-edit',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'User',
                'title' => 'Delete',
                'name' => 'users-delete',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'User',
                'title' => 'Unblock',
                'name' => 'users-unblock',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'User',
                'title' => 'Activate',
                'name' => 'users-activate',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'User',
                'title' => 'Deactivate',
                'name' => 'users-deactivate',
                'section' => 'Administration',
            ],

            [
                'title_tag' => 'User_Assign',
                'title' => 'List',
                'name' => 'assign-user-list',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'User_Assign',
                'title' => 'Create',
                'name' => 'assign-user-create',
                'section' => 'Administration',
            ],

            [
                'title_tag' => 'Password_Change',
                'name' => 'password-change',
                'title' => 'Change',
                'section' => 'Administration',
            ],

            [
                'title_tag' => 'Setting',
                'title' => 'Create',
                'name' => 'setting',
                'section' => 'Administration',
            ],

            // General
            [
                'title_tag' => 'Country',
                'title' => 'List',
                'name' => 'country-list',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Country',
                'title' => 'Create',
                'name' => 'country-create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Country',
                'title' => 'Edit',
                'name' => 'country-edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Country',
                'title' => 'Delete',
                'name' => 'country-delete',
                'section' => 'General',
            ],

            [
                'title_tag' => 'State',
                'title' => 'List',
                'name' => 'state-list',
                'section' => 'General',
            ],
            [
                'title_tag' => 'State',
                'title' => 'Create',
                'name' => 'state-create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'State',
                'title' => 'Edit',
                'name' => 'state-edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'State',
                'title' => 'Delete',
                'name' => 'state-delete',
                'section' => 'General',
            ],

            [
                'title_tag' => 'City',
                'title' => 'List',
                'name' => 'city-list',
                'section' => 'General',
            ],
            [
                'title_tag' => 'City',
                'title' => 'Create',
                'name' => 'city-create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'City',
                'title' => 'Edit',
                'name' => 'city-edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'City',
                'title' => 'Delete',
                'name' => 'city-delete',
                'section' => 'General',
            ],

            [
                'title_tag' => 'Unit',
                'title' => 'List',
                'name' => 'unit-list',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Unit',
                'title' => 'Create',
                'name' => 'unit-create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Unit',
                'title' => 'Edit',
                'name' => 'unit-edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Unit',
                'title' => 'Delete',
                'name' => 'unit-delete',
                'section' => 'General',
            ],

            [
                'title_tag' => 'Currency',
                'name' => 'currency-list',
                'title' => 'List',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Currency',
                'name' => 'currency-create',
                'title' => 'Create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Currency',
                'name' => 'currency-edit',
                'title' => 'Edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Currency',
                'name' => 'currency-delete',
                'title' => 'Delete',
                'section' => 'General',
            ],

            [
                'title_tag' => 'Year',
                'name' => 'year-list',
                'title' => 'List',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Year',
                'name' => 'year-create',
                'title' => 'Create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Year',
                'name' => 'year-edit',
                'title' => 'Edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Year',
                'name' => 'year-delete',
                'title' => 'Delete',
                'section' => 'General',
            ],

            [
                'title_tag' => 'Subscription',
                'name' => 'subscription-list',
                'title' => 'List',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Subscription',
                'name' => 'subscription-create',
                'title' => 'Create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Subscription',
                'name' => 'subscription-show',
                'title' => 'Show',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Subscription',
                'name' => 'subscription-edit',
                'title' => 'Edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'Subscription',
                'name' => 'subscription-delete',
                'title' => 'Delete',
                'section' => 'General',
            ],

            [
                'title_tag' => 'PgManagement',
                'name' => 'pgmanagement-list',
                'title' => 'List',
                'section' => 'General',
            ],
            [
                'title_tag' => 'PgManagement',
                'name' => 'pgmanagement-create',
                'title' => 'Create',
                'section' => 'General',
            ],
            [
                'title_tag' => 'PgManagement',
                'name' => 'pgmanagement-show',
                'title' => 'Show',
                'section' => 'General',
            ],
            [
                'title_tag' => 'PgManagement',
                'name' => 'pgmanagement-edit',
                'title' => 'Edit',
                'section' => 'General',
            ],
            [
                'title_tag' => 'PgManagement',
                'name' => 'pgmanagement-delete',
                'title' => 'Delete',
                'section' => 'General',
            ],

            // Reports
            [
                'title_tag' => 'Activity_Log_Report',
                'title' => 'List',
                'name' => 'report-list',
                'section' => 'Reports',
            ],
            [
                'title_tag' => 'Activity_Log_Report',
                'title' => 'Export',
                'name' => 'report-export',
                'section' => 'Reports',
            ],

            // Administration - Export & Masters
            [
                'title_tag' => 'Menu_Master_Export',
                'name' => 'menu-master-export',
                'title' => 'Export',
                'section' => 'Administration',
            ],

            [
                'title_tag' => 'Env_Variable',
                'title' => 'List',
                'name' => 'env-variable-list',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Env_Variable',
                'title' => 'Create',
                'name' => 'env-variable-create',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Env_Variable',
                'title' => 'Edit',
                'name' => 'env-variable-edit',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Env_Variable',
                'title' => 'Delete',
                'name' => 'env-variable-delete',
                'section' => 'Administration',
            ],

            [
                'title_tag' => 'Menu_Master',
                'title' => 'List',
                'name' => 'menu-master-list',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Menu_Master',
                'title' => 'Create',
                'name' => 'menu-master-create',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Menu_Master',
                'title' => 'Edit',
                'name' => 'menu-master-edit',
                'section' => 'Administration',
            ],
            [
                'title_tag' => 'Menu_Master',
                'title' => 'Delete',
                'name' => 'menu-master-delete',
                'section' => 'Administration',
            ],

            // Administration - System Administration (Super_Admin only)
            [
                'title_tag' => 'System_Administration',
                'name' => 'system-administration-access',
                'title' => 'Access',
                'section' => 'Administration',
            ],

        ];

        foreach ($permissions as $permissionData) {
            $existingPermission = Permission::where('name', $permissionData['name'])->first();
            if (! $existingPermission) {
                Permission::create([
                    'name' => $permissionData['name'],
                    'title' => $permissionData['title'],
                    'title_tag' => $permissionData['title_tag'],
                    'section' => $permissionData['section'],
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]);
            } else {
                // Fix any existing permissions with empty title/title_tag
                if (empty($existingPermission->title) || empty($existingPermission->title_tag)) {
                    $existingPermission->update([
                        'title' => $permissionData['title'],
                        'title_tag' => $permissionData['title_tag'],
                        'section' => $permissionData['section'],
                    ]);
                }
            }
        }

        // After creating permissions, sync them to Super_Admin and Pg_Admin roles
        $allPermissions = Permission::pluck('id', 'id')->all();

        // Get system administration permission
        $systemAdminPermission = Permission::where('name', 'system-administration-access')->first();

        // Sync all permissions to Super_Admin (including system-administration-access)
        $superAdminRole = Role::where('name', 'Super_Admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions($allPermissions);
        }

        // Sync all permissions EXCEPT system-administration-access to Pg_Admin
        $PgAdminRole = Role::where('name', 'Pg_Admin')->first();
        if ($PgAdminRole) {
            $companyPermissions = $allPermissions;
            if ($systemAdminPermission) {
                unset($companyPermissions[$systemAdminPermission->id]);
            }
            $PgAdminRole->syncPermissions($companyPermissions);
        }
    }
}
