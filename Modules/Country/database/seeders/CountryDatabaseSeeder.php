<?php

namespace Modules\Country\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;

class CountryDatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds.
     *
     * Delegates to CountryCityStateSeeder which fills countries + states + cities
     * from public/countries.json, states.json, cities.json (~157k rows total).
     * Per-module StateDatabaseSeeder / CityDatabaseSeeder are intentionally empty.
     */
    public function run(): void
    {
        $this->call([
            CountryCityStateSeeder::class,
        ]);
    }
}
