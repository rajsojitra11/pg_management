<?php

namespace Modules\MenuMaster\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuMasterDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $existingCount = DB::table('menu_masters')->count();

        if ($existingCount > 0) {
            // Default to true (truncate) for non-interactive mode (e.g. migrate:fresh --seed)
            if ($this->command?->confirm('Do you want to truncate the menu_masters table first?', true) ?? true) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('menu_master_logs')->truncate();
                DB::table('menu_masters')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        $menus = $this->getBasicMenus();

        $defaultDate = getDefaultMigrationDate();

        // Insert all menu items and create corresponding log entries
        foreach ($menus as $menu) {
            DB::table('menu_masters')->insert(array_merge($menu, [
                'public_id' => (string) Str::ulid(),
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));

            DB::table('menu_master_logs')->insert([
                'menu_master_id' => $menu['id'],
                'user_id' => 1,
                'activity' => 'System Record Creation',
                'user_remark' => 'Menu configuration seeded for navigation: '.$menu['menu_title'],
                'system_remark' => 'Initial Data Created By System Setup',
                'old_values' => null,
                'new_values' => json_encode(array_merge($menu, [
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ])),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Data Creator',
                'device' => 'Server',
                'platform' => 'Server',
                'browser' => 'Server',
                'created_by' => 1,
                'created_at' => $defaultDate,
            ]);
        }

        // Add System Administration as a separate parent (dynamic ID)
        $this->seedSystemAdminMenu($defaultDate);

        $this->command?->info('Menu seeded for boilerplate (surviving modules only).');
    }

    /**
     * Seed System Administration menu
     */
    private function seedSystemAdminMenu(string $defaultDate): void
    {
        $systemAdminMenuData = [
            'menu_icon' => 'fa-solid fa-shield-halved',
            'menu_title' => 'envvariable::message.system_administration',
            'menu_route' => 'javascript:void(0)',
            'parent_id' => null,
            'module_name' => null,
            'order_display' => '999',
            'display_order' => '999',
            'if_can' => 'system-administration-access',
            'is_main_menu' => 1,
            'public_id' => (string) Str::ulid(),
            'created_at' => $defaultDate,
            'updated_at' => $defaultDate,
            'created_by' => 1,
            'updated_by' => 1,
        ];

        $systemAdminId = DB::table('menu_masters')->insertGetId($systemAdminMenuData);

        DB::table('menu_master_logs')->insert([
            'menu_master_id' => $systemAdminId,
            'user_id' => 1,
            'activity' => 'System Record Creation',
            'user_remark' => 'System initialization',
            'system_remark' => 'System administration created navigation: '.$systemAdminMenuData['menu_title'],
            'old_values' => null,
            'new_values' => json_encode(array_merge($systemAdminMenuData, ['id' => $systemAdminId])),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'System Data Creator',
            'device' => 'Server',
            'platform' => 'Server',
            'browser' => 'Server',
            'created_by' => 1,
            'created_at' => $defaultDate,
        ]);

        $systemSubmenus = [
            [
                'menu_icon' => 'fa-solid fa-gears',
                'menu_title' => 'envvariable::message.env_variable',
                'menu_route' => 'env-variable.index',
                'module_name' => 'envvariable',
                'order_display' => '999.001',
                'display_order' => '999.1',
                'if_can' => 'system-administration-access',
                'is_main_menu' => 0,
            ],
        ];

        foreach ($systemSubmenus as $submenu) {
            $submenuData = array_merge($submenu, [
                'parent_id' => $systemAdminId,
                'public_id' => (string) Str::ulid(),
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $submenuId = DB::table('menu_masters')->insertGetId($submenuData);

            DB::table('menu_master_logs')->insert([
                'menu_master_id' => $submenuId,
                'user_id' => 1,
                'activity' => 'System Record Creation',
                'user_remark' => 'System administration submenu seeded for navigation: '.$submenu['menu_title'],
                'system_remark' => 'Initial Data Created By System Setup',
                'old_values' => null,
                'new_values' => json_encode(array_merge($submenuData, ['id' => $submenuId])),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Data Creator',
                'device' => 'Server',
                'platform' => 'Server',
                'browser' => 'Server',
                'created_by' => 1,
                'created_at' => $defaultDate,
            ]);
        }
    }

    /**
     * Basic menu layout — labels match the static reference at
     * Client Final theme/assets/js/erp-nav.js
     *
     * Parents:
     *   0   — Dashboard (single top-level link, no children)
     *   1   — Users (Role + User)
     *   2   — General Master (Country, State, City, Unit, Currency, Year, Setting)
     *   3   — Masters (Machine, Client, Vendor, Paper Coating/Finish/GSM, Paper, Plate Detail,
     *                  Printing Format, Sheet Size, Job Size, Post Press, Printing)
     *   4   — Job Card (Order Form, Create Order Form, Delivery Challan, Printing Job Detail,
     *                  Plate Detail Form, Lamination Order, UV Order)
     *   5   — Reports (Job Card Report, Delivery Challan Report)
     *   999 — System Administration (env-variable etc.) — seeded separately in seedSystemAdminMenu()
     */
    private function getBasicMenus(): array
    {
        return [
            // ── Top-level: Dashboard (id 0) ─────────────────────────────
            [
                'id' => 5000,
                'menu_icon' => 'fa-solid fa-house',
                'menu_title' => 'Dashboard',
                'menu_route' => 'dashboard',
                'is_main_menu' => 1,
                'parent_id' => null,
                'module_name' => 'Dashbord',
                'order_display' => '000',
                'display_order' => '0',
                'if_can' => null,
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // ── Parent: Users (id 1) ────────────────────────────────────
            [
                'id' => 1,
                'menu_icon' => 'fa-solid fa-users',
                'menu_title' => 'user::message.users',
                'menu_route' => 'javascript:void(0)',
                'is_main_menu' => 1,
                'parent_id' => null,
                'module_name' => null,
                'order_display' => '001',
                'display_order' => '1',
                'if_can' => 'role-list,users-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 101,
                'menu_icon' => 'fa-solid fa-user-shield',
                'menu_title' => 'role::message.roles',
                'menu_route' => 'roles.index',
                'is_main_menu' => 0,
                'parent_id' => 1,
                'module_name' => 'Role',
                'order_display' => '001.001',
                'display_order' => '1.1',
                'if_can' => 'role-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 102,
                'menu_icon' => 'fa-solid fa-user',
                'menu_title' => 'user::message.users',
                'menu_route' => 'users.index',
                'is_main_menu' => 0,
                'parent_id' => 1,
                'module_name' => 'User',
                'order_display' => '001.002',
                'display_order' => '1.2',
                'if_can' => 'users-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 3,
                'menu_icon' => 'fa-solid fa-file-invoice',
                'menu_title' => 'subscription::message.subscription',
                'menu_route' => 'subscription.index',
                'is_main_menu' => 0,
                'parent_id' => null,
                'module_name' => 'Subscription',
                'order_display' => '001.003',
                'display_order' => '1.3',
                'if_can' => 'subscription-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 4,
                'menu_icon' => 'fa-solid fa-building',
                'menu_title' => 'pgmanagement::message.pg_management',
                'menu_route' => 'pgmanagement.index',
                'is_main_menu' => 0,
                'parent_id' => null,
                'module_name' => 'PgManagement',
                'order_display' => '001.004',
                'display_order' => '1.4',
                'if_can' => 'pgmanagement-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // ── Parent: Rooms (id 5) ───────────────────────────────────
            [
                'id' => 5,
                'menu_icon' => 'fa-solid fa-door-open',
                'menu_title' => 'room::message.rooms',
                'menu_route' => 'javascript:void(0)',
                'is_main_menu' => 1,
                'parent_id' => null,
                'module_name' => null,
                'order_display' => '001.005',
                'display_order' => '1.5',
                'if_can' => 'room-category-list,room-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 503,
                'menu_icon' => 'fa-solid fa-layer-group',
                'menu_title' => 'room::message.room_category',
                'menu_route' => 'room-category.index',
                'is_main_menu' => 0,
                'parent_id' => 5,
                'module_name' => 'Room',
                'order_display' => '001.005.001',
                'display_order' => '1.5.1',
                'if_can' => 'room-category-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 504,
                'menu_icon' => 'fa-solid fa-bed',
                'menu_title' => 'room::message.room',
                'menu_route' => 'room.index',
                'is_main_menu' => 0,
                'parent_id' => 5,
                'module_name' => 'Room',
                'order_display' => '001.005.002',
                'display_order' => '1.5.2',
                'if_can' => 'room-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 6,
                'menu_icon' => 'fa-solid fa-bullhorn',
                'menu_title' => 'noticeboard::message.noticeboards',
                'menu_route' => 'noticeboard.index',
                'is_main_menu' => 0,
                'parent_id' => null,
                'module_name' => 'Noticeboard',
                'order_display' => '001.006',
                'display_order' => '1.6',
                'if_can' => 'noticeboard-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // ── Parent: Services (id 8) ─────────────────────────────────
            [
                'id' => 8,
                'menu_icon' => 'fa-solid fa-concierge-bell',
                'menu_title' => 'service::message.module_name',
                'menu_route' => 'javascript:void(0)',
                'is_main_menu' => 1,
                'parent_id' => null,
                'module_name' => null,
                'order_display' => '001.008',
                'display_order' => '1.8',
                'if_can' => 'service-category-list,service-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 801,
                'menu_icon' => 'fa-solid fa-layer-group',
                'menu_title' => 'service::message.categories',
                'menu_route' => 'service-category.index',
                'is_main_menu' => 0,
                'parent_id' => 8,
                'module_name' => 'Service',
                'order_display' => '001.008.001',
                'display_order' => '1.8.1',
                'if_can' => 'service-category-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 802,
                'menu_icon' => 'fa-solid fa-hand-sparkles',
                'menu_title' => 'service::message.services',
                'menu_route' => 'service.index',
                'is_main_menu' => 0,
                'parent_id' => 8,
                'module_name' => 'Service',
                'order_display' => '001.008.002',
                'display_order' => '1.8.2',
                'if_can' => 'service-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // ── Parent: Tenant (id 7) ───────────────────────────────────
            [
                'id' => 7,
                'menu_icon' => 'fa-solid fa-user-group',
                'menu_title' => 'tenant::message.tenant',
                'menu_route' => 'javascript:void(0)',
                'is_main_menu' => 1,
                'parent_id' => null,
                'module_name' => null,
                'order_display' => '001.007',
                'display_order' => '1.7',
                'if_can' => 'tenant-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 701,
                'menu_icon' => 'fa-solid fa-users-gear',
                'menu_title' => 'tenant::message.manage_tenant',
                'menu_route' => 'tenant.index',
                'is_main_menu' => 0,
                'parent_id' => 7,
                'module_name' => 'Tenant',
                'order_display' => '001.007.001',
                'display_order' => '1.7.1',
                'if_can' => 'tenant-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 702,
                'menu_icon' => 'fa-solid fa-money-bill-wave',
                'menu_title' => 'payment::message.payments',
                'menu_route' => 'payment.index',
                'is_main_menu' => 0,
                'parent_id' => 7,
                'module_name' => 'Payment',
                'order_display' => '001.007.002',
                'display_order' => '1.7.2',
                'if_can' => 'payment-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            [
                'id' => 9,
                'menu_icon' => 'fa-solid fa-circle-exclamation',
                'menu_title' => 'complaint::message.complaints',
                'menu_route' => 'complaint.index',
                'is_main_menu' => 0,
                'parent_id' => null,
                'module_name' => 'Complaint',
                'order_display' => '001.009',
                'display_order' => '1.9',
                'if_can' => 'complaint-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            [
                'id' => 10,
                'menu_icon' => 'fa-solid fa-wrench',
                'menu_title' => 'maintenance::message.module_name',
                'menu_route' => 'maintenance.index',
                'is_main_menu' => 0,
                'parent_id' => null,
                'module_name' => 'Maintenance',
                'order_display' => '001.010',
                'display_order' => '1.10',
                'if_can' => 'maintenance-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],

            // ── Parent: General Master (id 2) ──────────────────────────
            [
                'id' => 2,
                'menu_icon' => 'fa-solid fa-globe',
                'menu_title' => 'lang.general_master',
                'menu_route' => 'javascript:void(0)',
                'is_main_menu' => 1,
                'parent_id' => null,
                'module_name' => null,
                'order_display' => '002',
                'display_order' => '2',
                'if_can' => 'country-list,state-list,city-list,unit-list,currency-list,year-list,setting',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 201,
                'menu_icon' => 'fa-solid fa-flag',
                'menu_title' => 'country::message.country',
                'menu_route' => 'country.index',
                'is_main_menu' => 0,
                'parent_id' => 2,
                'module_name' => 'Country',
                'order_display' => '002.001',
                'display_order' => '2.1',
                'if_can' => 'country-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 202,
                'menu_icon' => 'fa-solid fa-location-dot',
                'menu_title' => 'state::message.state',
                'menu_route' => 'state.index',
                'is_main_menu' => 0,
                'parent_id' => 2,
                'module_name' => 'State',
                'order_display' => '002.002',
                'display_order' => '2.2',
                'if_can' => 'state-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 203,
                'menu_icon' => 'fa-solid fa-building',
                'menu_title' => 'city::message.city',
                'menu_route' => 'city.index',
                'is_main_menu' => 0,
                'parent_id' => 2,
                'module_name' => 'City',
                'order_display' => '002.003',
                'display_order' => '2.3',
                'if_can' => 'city-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 204,
                'menu_icon' => 'fa-solid fa-ruler',
                'menu_title' => 'unit::message.unit',
                'menu_route' => 'unit.index',
                'is_main_menu' => 0,
                'parent_id' => 2,
                'module_name' => 'Unit',
                'order_display' => '002.004',
                'display_order' => '2.4',
                'if_can' => 'unit-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 205,
                'menu_icon' => 'fa-solid fa-coins',
                'menu_title' => 'currency::message.currency',
                'menu_route' => 'currency.index',
                'is_main_menu' => 0,
                'parent_id' => 2,
                'module_name' => 'Currency',
                'order_display' => '002.005',
                'display_order' => '2.5',
                'if_can' => 'currency-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 206,
                'menu_icon' => 'fa-solid fa-calendar',
                'menu_title' => 'year::message.year',
                'menu_route' => 'year.index',
                'is_main_menu' => 0,
                'parent_id' => 2,
                'module_name' => 'Year',
                'order_display' => '002.006',
                'display_order' => '2.6',
                'if_can' => 'year-list',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'id' => 207,
                'menu_icon' => 'fa-solid fa-gear',
                'menu_title' => 'setting::message.setting',
                'menu_route' => 'setting.index',
                'is_main_menu' => 0,
                'parent_id' => 2,
                'module_name' => 'Setting',
                'order_display' => '002.007',
                'display_order' => '2.7',
                'if_can' => 'setting',
                'created_by' => 1,
                'updated_by' => 1,
            ],

        ];
    }
}
