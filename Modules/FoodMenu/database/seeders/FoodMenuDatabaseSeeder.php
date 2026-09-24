<?php

namespace Modules\FoodMenu\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\FoodMenu\Models\FoodMenu;
use Modules\FoodMenu\Models\FoodMenuItem;
use Modules\User\Models\User;

class FoodMenuDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        if (FoodMenu::count() > 0) {
            $this->command->info('FoodMenu data already exists. Skipping seeding.');

            return;
        }

        $pg = DB::table('pg_management')->first();
        $superAdmin = User::where('username', 'super_admin')->first();

        if (! $pg || ! $superAdmin) {
            $this->command->warn('No default PG or super_admin user found. Skipping FoodMenu seeding.');

            return;
        }

        $menu = $this->createWithLogging(FoodMenu::class, [
            'user_id' => $superAdmin->id,
            'pg_id' => $pg->id,
            'menu_type' => 'weekly',
            'title' => 'Standard Weekly Menu',
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'special_date' => null,
            'status' => 'active',
        ]);

        $weeklyItems = [
            ['monday', 'breakfast', 'Poha'],
            ['monday', 'lunch', 'Dal Tadka + Jeera Rice'],
            ['monday', 'dinner', 'Mix Veg + Roti'],
            ['tuesday', 'breakfast', 'Aloo Paratha'],
            ['tuesday', 'lunch', 'Rajma + Rice'],
            ['wednesday', 'dinner', 'Paneer Butter Masala + Naan'],
            ['friday', 'dinner', 'Biriyani'],
        ];

        foreach ($weeklyItems as [$day, $mealTime, $itemName]) {
            $this->createWithLogging(FoodMenuItem::class, [
                'food_menu_id' => $menu->id,
                'day' => $day,
                'meal_time' => $mealTime,
                'item_name' => $itemName,
                'description' => null,
                'sort_order' => 0,
                'status' => 'active',
            ]);
        }

        $special = $this->createWithLogging(FoodMenu::class, [
            'user_id' => $superAdmin->id,
            'pg_id' => $pg->id,
            'menu_type' => 'special',
            'title' => 'Holi Special',
            'week_start_date' => null,
            'special_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        $specialItems = [
            ['breakfast', 'Gujarati Thali'],
            ['lunch', 'Thandai'],
            ['dinner', 'Malpua'],
        ];

        foreach ($specialItems as [$mealTime, $itemName]) {
            $this->createWithLogging(FoodMenuItem::class, [
                'food_menu_id' => $special->id,
                'day' => null,
                'meal_time' => $mealTime,
                'item_name' => $itemName,
                'description' => null,
                'sort_order' => 0,
                'status' => 'active',
            ]);
        }

        $this->command->info('FoodMenu seeded successfully.');
    }
}
