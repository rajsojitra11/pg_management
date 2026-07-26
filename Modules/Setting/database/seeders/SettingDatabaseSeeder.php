<?php

namespace Modules\Setting\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Models\Setting;

class SettingDatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->call([]);

        $countryId = null;
        $stateId = null;
        $cityId = null;

        $countryResult = DB::table('countries')->where('code', 'IN')->first();
        if ($countryResult) {
            $countryId = $countryResult->id;
        }

        $stateResult = DB::table('states')->where('code', 'GJ')->where('country_id', $countryId)->first();
        if ($stateResult) {
            $stateId = $stateResult->id;
        }

        $cityResult = DB::table('cities')->where('name', 'Rajkot')->where('country_id', $countryId)->where('state_id', $stateId)->first();
        if ($cityResult) {
            $cityId = $cityResult->id;
        }

        $defaultDate = getDefaultMigrationDate();

        $setting = Setting::create([
            'company_name' => 'Sample Company',
            'mobile' => '9876543210',
            'email' => 'samplecompany@samplecompany.com',
            'address' => 'Sample Address',
            'tag_line' => 'Sample Tagline',
            'gst_number' => '24AAACC1206D1ZM',
            'pancard_number' => 'AAACC1206D',
            'tan_number' => 'AAACC1206D',
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => $defaultDate,
            'updated_at' => $defaultDate,
        ]);

        // Update the automatically created log entry to use seeder system remark and user remark
        DB::table('setting_logs')
            ->where('setting_id', $setting->id)
            ->where('activity', 'created')
            ->update([
                'new_values' => json_encode($setting),
                'user_agent' => 'System Data Creator',
                'device' => 'Server',
                'platform' => 'Server',
                'browser' => 'Server',
                'system_remark' => 'Initial Data Created By System Setup',
            ]);
    }
}
