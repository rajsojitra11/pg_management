<?php

namespace Modules\Year\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
use Modules\Year\Models\Year;

class YearDatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->call([]);

        $startYear = date('Y') - 3;
        $endYear = $startYear + 150;
        $insertYears = [];

        $createdUpdateBy = 0;
        $createdUpdateByResult = DB::table('users')->where('username', 'super_admin')->first();
        if ($createdUpdateByResult) {
            $createdUpdateBy = $createdUpdateByResult->id;
        } else {
            UserDatabaseSeeder::class;
            $createdUpdateByResult = DB::table('users')->where('username', 'super_admin')->first();
            if ($createdUpdateByResult) {
                $createdUpdateBy = $createdUpdateByResult->id;
            }
        }

        $existingCount = DB::table('years')->count();

        if ($existingCount > 0) {

            if ($this->command?->confirm('Do you want to truncate the years table first?', true) ?? true) {
                $this->safeTruncate('years');
            }
        }

        $defaultDate = getDefaultMigrationDate();
        $fyStartMonth = config('business.fy_start_month', 4);

        // Determine current FY start year based on today and FY start month
        $currentMonth = (int) date('m');
        $currentCalendarYear = (int) date('Y');
        if ($fyStartMonth === 1) {
            // Jan-Dec: current FY = current calendar year
            $currentFYStartYear = $currentCalendarYear;
        } else {
            // Multi-month FY (e.g., Apr-Mar): if before FY start month, current FY started last year
            $currentFYStartYear = $currentMonth >= $fyStartMonth ? $currentCalendarYear : $currentCalendarYear - 1;
        }

        for ($year = $startYear; $year <= $endYear; $year++) {

            $fullYear = $year;
            $shortYear = substr($year, -2);

            if ($fyStartMonth === 1) {
                // Calendar year (Jan-Dec): start and end year are the same
                $nextYearFull = $year;
                $nextYearShort = $shortYear;
            } else {
                // Fiscal year (e.g., Apr-Mar): end year is next calendar year
                $nextYearFull = $year + 1;
                $nextYearShort = substr($nextYearFull, -2);
            }

            $set_default = ($year == $currentFYStartYear) ? 1 : 0;
            $insertYears[] = [
                'name' => $fullYear.'-'.$nextYearShort,
                'full_short' => $fullYear.'-'.$nextYearShort, // YYYY-YY
                'short_full' => $shortYear.'-'.$nextYearFull, // YY-YYYY
                'short_short' => $shortYear.'-'.$nextYearShort, // YY-YY
                'full_full' => $fullYear.'-'.$nextYearFull, // YYYY-YYYY
                'short' => $shortYear, // YY
                'full' => $fullYear, // YYYY
                'set_default' => $set_default,
                'created_by' => $createdUpdateBy,
                'created_at' => $defaultDate,
                'updated_by' => $createdUpdateBy,
                'updated_at' => $defaultDate,
            ];
        }

        DB::transaction(function () use ($insertYears, $createdUpdateBy, $defaultDate) {
            // Fast bulk insert
            DB::table('years')->insert($insertYears);

            // Get the inserted records to create log entries
            $insertedYears = DB::table('years')
                ->whereIn('name', array_column($insertYears, 'name'))
                ->get();

            // Create manual log entries
            $this->createYearLogEntries($insertedYears, $createdUpdateBy, $defaultDate);
        });
    }

    /**
     * Create manual log entries for bulk inserted year records
     */
    private function createYearLogEntries($years, $createdBy, $createdAt): void
    {
        $logRecords = [];

        foreach ($years as $year) {
            // Generate system remark based on year name
            $userRemark = $year->name;

            $userRemark = 'Initial Data Created By System Setup';

            $logRecords[] = [
                'year_id' => $year->id,
                'user_id' => $createdBy,
                'activity' => 'System Record Creation',
                'user_remark' => $userRemark,
                'system_remark' => 'Initial Data Created By System Setup',
                'old_values' => null,
                'new_values' => json_encode([
                    'id' => $year->id,
                    'name' => $year->name,
                    'set_default' => $year->set_default,
                    'created_by' => $createdBy,
                    'created_at' => $createdAt,
                    'updated_by' => $createdBy,
                    'updated_at' => $createdAt,
                ]),
                'ip_address' => '127.0.0.1', // Default for seeder
                'user_agent' => 'System Data Creator',
                'device' => 'Server',
                'platform' => 'Server',
                'browser' => 'Server',
                'created_by' => $createdBy,
                'created_at' => $createdAt,
            ];
        }

        // Bulk insert log entries
        if (! empty($logRecords)) {
            DB::table('year_logs')->insert($logRecords);
        }
    }
}
