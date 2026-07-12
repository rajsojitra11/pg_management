<?php

namespace Modules\EnvVariable\Database\Seeders;

use App\Traits\SeederLogging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\EnvVariable\Models\EnvVariable;
use Modules\User\Models\User;

class EnvVariableDatabaseSeeder extends Seeder
{
    use SeederLogging;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();
        $superAdminId = 1; // Assuming Super_Admin user has ID 1

        $variables = [
            // Menu Configuration
            [
                'key' => 'NAVIGATION_MENU_THEME',
                'value' => 'vertical',
                'type' => 'select',
                'options' => ['vertical', 'horizontal'],
                'category' => 'UI',
                'description' => 'Menu style layout (vertical or horizontal)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 1,
            ],
            // Application Configuration
            [
                'key' => 'SYSTEM_MIGRATION_BASE_DATE',
                'value' => '2025-01-01 00:00:00',
                'type' => 'text',
                'category' => 'Application',
                'description' => 'Default date to use when ENABLE_HISTORICAL_DATA_ENTRY is enabled',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 18,
            ],
            [
                'key' => 'COMPANY_NAME',
                'value' => 'PG Management',
                'type' => 'text',
                'category' => 'Company',
                'description' => 'Company name',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 19,
            ],
            [
                'key' => 'COMPANY_ADDRESS',
                'value' => '14/A Lohanagar, Nr. Pavagadhi Estate, B/h Passport Office, Gondal road, Rajkot',
                'type' => 'text',
                'category' => 'Company',
                'description' => 'Company address',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 19,
            ],
            [
                'key' => 'APP_DEBUG',
                'value' => env('APP_DEBUG', 'false'),
                'type' => 'boolean',
                'category' => 'Application',
                'description' => 'Enable or disable debug mode for the application',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 20,
            ],
            [
                'key' => 'APP_NAME',
                'value' => 'PG Management',
                'type' => 'text',
                'category' => 'Application',
                'description' => 'Application name',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 21,
            ],
            [
                'key' => 'APP_ENV',
                'value' => 'local',
                'type' => 'select',
                'options' => ['local', 'development', 'staging', 'testing', 'production'],
                'category' => 'Application',
                'description' => 'Application environment',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 22,
            ],
            [
                'key' => 'APP_TIMEZONE',
                'value' => 'Asia/Kolkata',
                'type' => 'select',
                'options' => \DateTimeZone::listIdentifiers(),
                'category' => 'Application',
                'description' => 'Application timezone',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 23,
            ],
            // Mail Configuration
            [
                'key' => 'MAIL_MAILER',
                'value' => env('MAIL_MAILER', 'smtp'),
                'type' => 'select',
                'options' => ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array', 'failover', 'roundrobin'],
                'category' => 'Mail',
                'description' => 'Mail driver/mailer type',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 24,
            ],
            [
                'key' => 'MAIL_HOST',
                'value' => env('MAIL_HOST', 'mailpit'),
                'type' => 'text',
                'category' => 'Mail',
                'description' => 'SMTP server hostname',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 25,
            ],
            [
                'key' => 'MAIL_PORT',
                'value' => env('MAIL_PORT', '1025'),
                'type' => 'number',
                'category' => 'Mail',
                'description' => 'SMTP server port',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 26,
            ],
            [
                'key' => 'MAIL_USERNAME',
                'value' => env('MAIL_USERNAME'),
                'type' => 'text',
                'category' => 'Mail',
                'description' => 'SMTP username',
                'is_sensitive' => true,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 27,
            ],
            [
                'key' => 'MAIL_PASSWORD',
                'value' => env('MAIL_PASSWORD'),
                'type' => 'password',
                'category' => 'Mail',
                'description' => 'SMTP password',
                'is_sensitive' => true,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 28,
            ],
            [
                'key' => 'MAIL_ENCRYPTION',
                'value' => env('MAIL_ENCRYPTION'),
                'type' => 'select',
                'options' => [null, 'tls', 'ssl'],
                'category' => 'Mail',
                'description' => 'SMTP encryption type (none, tls, ssl)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 29,
            ],
            [
                'key' => 'MAIL_FROM_ADDRESS',
                'value' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'type' => 'text',
                'category' => 'Mail',
                'description' => 'Default from email address',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 30,
            ],
            [
                'key' => 'MAIL_FROM_NAME',
                'value' => env('MAIL_FROM_NAME', '${APP_NAME}'),
                'type' => 'text',
                'category' => 'Mail',
                'description' => 'Default from name for emails',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 31,
            ],
            // Performance Configuration
            [
                'key' => 'SESSION_DRIVER',
                'value' => 'database',
                'type' => 'select',
                'options' => ['file', 'database', 'redis', 'memcached', 'dynamodb'],
                'category' => 'Performance',
                'description' => 'Session storage driver (database recommended for multi-server)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 32,
            ],
            [
                'key' => 'CACHE_DRIVER',
                'value' => 'database',
                'type' => 'select',
                'options' => ['file', 'database', 'redis', 'memcached', 'dynamodb', 'array'],
                'category' => 'Performance',
                'description' => 'Cache storage driver (database recommended for multi-server)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 33,
            ],
            [
                'key' => 'QUEUE_CONNECTION',
                'value' => 'database',
                'type' => 'select',
                'options' => ['sync', 'database', 'redis', 'beanstalkd', 'sqs'],
                'category' => 'Performance',
                'description' => 'Queue driver (database or redis recommended for production)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 34,
            ],
            [
                'key' => 'CACHE_PREFIX',
                'value' => 'pg_management_cache',
                'type' => 'text',
                'validation_rules' => 'required|string|max:50|alpha_dash',
                'category' => 'Performance',
                'description' => 'Cache key prefix for cache isolation',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 35,
            ],
            [
                'key' => 'SESSION_LIFETIME',
                'value' => '480',
                'type' => 'number',
                'validation_rules' => 'required|integer|min:30|max:1440',
                'category' => 'Performance',
                'description' => 'Session lifetime in minutes (30-1440 range)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 36,
            ],
            [
                'key' => 'BCRYPT_ROUNDS',
                'value' => '12',
                'type' => 'number',
                'validation_rules' => 'required|integer|min:10|max:15',
                'category' => 'Performance',
                'description' => 'Bcrypt hashing rounds (10-15 range, higher = more secure but slower)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 37,
            ],
            [
                'key' => 'PHP_CLI_SERVER_WORKERS',
                'value' => '4',
                'type' => 'number',
                'validation_rules' => 'required|integer|min:1|max:8',
                'category' => 'Performance',
                'description' => 'Number of PHP CLI server workers (1-8 range)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 38,
            ],
            [
                'key' => 'LOG_LEVEL',
                'value' => 'debug',
                'type' => 'select',
                'options' => ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
                'category' => 'Performance',
                'description' => 'Log level for application logging (error recommended for production)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => false,
                'sort_order' => 39,
            ],
            [
                'key' => 'APP_MAINTENANCE_DRIVER',
                'value' => 'file',
                'type' => 'select',
                'options' => ['file', 'database'],
                'category' => 'Performance',
                'description' => 'Maintenance mode storage driver (database recommended for multi-server)',
                'is_sensitive' => false,
                'is_editable' => true,
                'requires_restart' => true,
                'sort_order' => 40,
            ],
        ];

        foreach ($variables as $variableData) {
            // Only create if it doesn't exist
            $exists = EnvVariable::where('key', $variableData['key'])->exists();

            if (! $exists) {
                $defaultDate = getDefaultMigrationDate();

                // JSON-encode options field if it's an array
                if (isset($variableData['options']) && is_array($variableData['options'])) {
                    $variableData['options'] = json_encode($variableData['options']);
                }

                // Create without triggering automatic logging by using DB::table insert
                $envVariableId = DB::table('env_variables')->insertGetId(array_merge($variableData, [
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]));

                // Manually create log entry with seeder system remark and user remark
                DB::table('env_variable_logs')->insert([
                    'env_variable_id' => $envVariableId,
                    'user_id' => 1,
                    'activity' => 'System Record Creation',
                    'user_remark' => 'Environment variable seeded for '.$variableData['category'].' configuration: '.$variableData['key'],
                    'system_remark' => 'Initial Data Created By System Setup',
                    'old_values' => null,
                    'new_values' => json_encode(array_merge($variableData, [
                        'id' => $envVariableId,
                        'created_by' => 1,
                        'updated_by' => 1,
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
        }

        // Clear configuration cache after seeding all variables
        $envVariable = new EnvVariable;
        $envVariable->clearAllCaches();

        // Sync all variables to .env file after seeding
        $syncSuccess = EnvVariable::syncToEnvFile();

        if ($syncSuccess) {
            echo "Environment variables synced to .env file successfully.\n";
        } else {
            echo "Warning: Could not sync environment variables to .env file.\n";
        }
    }
}
