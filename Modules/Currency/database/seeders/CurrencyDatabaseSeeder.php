<?php

namespace Modules\Currency\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Currency\Models\Currency;

class CurrencyDatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        // Sample currencies data
        $currencies = [
            [
                'currency_name' => 'Indian Rupee',
                'currency_symbol' => '₹',
            ],
            [
                'currency_name' => 'US Dollar',
                'currency_symbol' => '$',
            ],
            [
                'currency_name' => 'Euro',
                'currency_symbol' => '€',
            ],
            [
                'currency_name' => 'British Pound',
                'currency_symbol' => '£',
            ],
        ];

        foreach ($currencies as $currency) {
            $currencyRecord = Currency::create(array_merge($currency, [
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]));

            // Update the automatically created log entry to use seeder system remark
            DB::table('currency_logs')
                ->where('currency_id', $currencyRecord->id)
                ->where('activity', 'created')
                ->update([
                    'new_values' => json_encode($currencyRecord),
                    'user_agent' => 'System Data Creator',
                    'device' => 'Server',
                    'platform' => 'Server',
                    'browser' => 'Server',
                    'system_remark' => 'Initial Data Created By System Setup',

                ]);
        }

        $this->command->info('Currency seeding completed successfully.');
    }
}
