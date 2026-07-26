<?php

namespace Modules\Country\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Country\Models\Country;
use Modules\User\Database\Seeders\UserDatabaseSeeder;

class CountryCityStateSeeder extends Seeder
{
    private int $chunkSize = 2500;

    private int $totalSize = 0;

    private array $buffer = [];

    private $startTime;

    // Counters
    private $countryCount = 0;

    private $stateCount = 0;

    private $cityCount = 0;

    public function run()
    {

        $countriesFile = 'countries.json';
        $this->databaseSeed('countries', $countriesFile);
        $totalTime = microtime(true) - $this->startTime;
        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Countries Imported', number_format($this->countryCount)],
                ['Execution Time', gmdate('H:i:s', $totalTime).' ('.round($totalTime, 2).'ms)'],
                ['Records per Second', round(($this->countryCount) / $totalTime, 2)],
            ]
        );

        $statesFile = 'states.json';
        $this->databaseSeed('states', $statesFile);
        $totalTime = microtime(true) - $this->startTime;
        $this->command->table(
            ['Metric', 'Value'],
            [
                ['States Imported', number_format($this->stateCount)],
                ['Execution Time', gmdate('H:i:s', $totalTime).' ('.round($totalTime, 2).'ms)'],
                ['Records per Second', round(($this->stateCount) / $totalTime, 2)],
            ]
        );

        $citiesFile = 'cities.json';
        $this->databaseSeed('cities', $citiesFile);
        $totalTime = microtime(true) - $this->startTime;
        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Cities Imported', number_format($this->cityCount)],
                ['Execution Time', gmdate('H:i:s', $totalTime).' ('.round($totalTime, 2).'ms)'],
                ['Records per Second', round(($this->cityCount) / $totalTime, 2)],
            ]
        );
    }

    private function databaseSeed($table, $jsonFileName)
    {

        $this->startTime = microtime(true);
        $this->command->info("Starting streaming $table import...");

        // Increase memory limit temporarily
        ini_set('memory_limit', '256M');

        // Disable query log to save memory
        DB::disableQueryLog();

        $jsonFile = public_path($jsonFileName);

        if (! File::exists($jsonFile)) {
            throw new Exception("JSON file not found at: {$jsonFile}");
        }

        // Clear existing data

        $existingCount = DB::table($table)->count();

        if ($existingCount > 0) {

            if ($this->command?->confirm("Do you want to truncate the $table table first?", true) ?? true) {
                Schema::disableForeignKeyConstraints();
                if (DB::getDriverName() === 'pgsql') {
                    DB::statement('TRUNCATE TABLE "'.$table.'" CASCADE');
                } else {
                    DB::table($table)->truncate();
                }
                Schema::enableForeignKeyConstraints();
            }
        }

        // Set longer execution time for CLI
        if (php_sapi_name() === 'cli') {
            set_time_limit(0); // No time limit for CLI
            ini_set('memory_limit', '2G'); // Increase memory for seeding
        }

        $this->command->info("🚀 Starting {$table} Data Import...");

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

        $this->streamJsonFile($jsonFile, $table, $createdUpdateBy);

        // Insert any remaining items in buffer
        if (! empty($this->buffer)) {
            $this->insertBuffer($table);
        }

        $this->command->info("Streaming {$table} import completed!");
    }

    /**
     * Stream and process the JSON file
     */
    private function streamJsonFile(string $filePath, $table, $createdUpdateBy): void
    {
        // For JSON arrays, we need to read the complete file
        // But we can optimize memory usage

        $this->command->info("📁 Reading file: {$filePath}");

        $jsonString = $this->readFileInChunks($filePath);
        $databaseEntries = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('JSON decode error: '.json_last_error_msg());

            return;
        }

        $this->totalSize = $total = count($databaseEntries);
        $processed = 0;

        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();

        foreach ($databaseEntries as $entry) {
            $this->addToBuffer($this->transformCountryData($entry, $table, $createdUpdateBy));
            $processed++;
            if ($table == 'countries') {
                $this->countryCount++;
            } elseif ($table == 'states') {
                $this->stateCount++;
            } elseif ($table == 'cities') {
                $this->cityCount++;
            }

            if (count($this->buffer) >= $this->chunkSize) {

                $this->insertBuffer($table);

                $progressBar->advance($this->chunkSize);

                // Optional: Add small delay to prevent database overload
                usleep(5000); // 5ms
            }
        }

        $progressBar->finish();
        $this->command->line('');

        // Clean up memory
        unset($databaseEntries, $jsonString);
    }

    /**
     * Read file in chunks to reduce memory usage
     */
    private function readFileInChunks(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        $content = '';

        while (! feof($handle)) {
            $content .= fread($handle, 8192); // 8KB chunks
        }

        fclose($handle);

        return $content;
    }

    /**
     * Transform country data for database insertion
     */
    private function transformCountryData(array $entry, $table, $createdUpdateBy): array
    {
        $defaultDate = getDefaultMigrationDate();

        if ($table == 'countries') {
            return [
                'id' => $entry['id'],
                'name' => $entry['name'],
                'code' => $entry['iso2'],
                'created_by' => $createdUpdateBy,
                'created_at' => $defaultDate,
                'updated_by' => $createdUpdateBy,
                'updated_at' => $defaultDate,
            ];
        } elseif ($table == 'states') {
            $is_ut = 'N';
            if ($entry['type'] == 'union territory') {
                $is_ut = 'Y';
            }

            return [
                'id' => $entry['id'],
                'country_id' => $entry['country_id'],
                'name' => $entry['name'],
                'code' => $entry['state_code'],
                'is_ut' => $is_ut,
                'created_by' => $createdUpdateBy,
                'created_at' => $defaultDate,
                'updated_by' => $createdUpdateBy,
                'updated_at' => $defaultDate,
            ];
        } elseif ($table == 'cities') {
            return [
                'id' => $entry['id'],
                'country_id' => $entry['country_id'],
                'state_id' => $entry['state_id'],
                'name' => $entry['name'],
                'created_by' => $createdUpdateBy,
                'created_at' => $defaultDate,
                'updated_by' => $createdUpdateBy,
                'updated_at' => $defaultDate,
            ];
        }

        // Default case - should never be reached with valid table names
        throw new \InvalidArgumentException("Invalid table name: {$table}. Expected 'countries', 'states', or 'cities'.");
    }

    /**
     * Add item to buffer
     */
    private function addToBuffer(array $item): void
    {
        $this->buffer[] = $item;
    }

    /**
     * Insert buffer contents to database and clear buffer
     */
    private function insertBuffer($table): void
    {
        if (empty($this->buffer)) {
            return;
        }

        try {
            DB::transaction(function () use ($table) {
                // Fast bulk insert
                DB::table($table)->insert($this->buffer);

                // Manual logging for each record
                $this->createLogEntries($table, $this->buffer);
            });
        } catch (Exception $e) {
            $this->command->error('Database insert error: '.$e->getMessage());
            throw $e;
        }

        // Clear buffer to free memory
        $this->buffer = [];

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Create manual log entries for bulk inserted records
     */
    private function createLogEntries($table, $records): void
    {
        $logRecords = [];
        $logTable = $table === 'countries' ? 'country_logs' :
                   ($table === 'states' ? 'state_logs' : 'city_logs');

        foreach ($records as $record) {
            $moduleId = $record['id'];
            $createdAt = $record['created_at'];
            $createdBy = $record['created_by'];

            // Generate system remark and user remark based on table type
            $systemRemark = 'Initial Data Created By System Setup';
            $userRemark = match ($table) {
                'countries' => 'Master country data seeded for location setup: '.($record['name'] ?? "Record ID: {$moduleId}"),
                'states' => 'Master state data seeded for location setup: '.($record['name'] ?? "Record ID: {$moduleId}"),
                'cities' => 'Master city data seeded for location setup: '.($record['name'] ?? "Record ID: {$moduleId}"),
                default => 'Master location data seeded for system setup'
            };

            $logRecords[] = [
                $table === 'countries' ? 'country_id' :
                ($table === 'states' ? 'state_id' : 'city_id') => $moduleId,
                'user_id' => $createdBy,
                'activity' => 'System Record Creation',
                'system_remark' => $systemRemark,
                'old_values' => null,
                'new_values' => json_encode($record),
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
            DB::table($logTable)->insert($logRecords);
        }
    }
}
