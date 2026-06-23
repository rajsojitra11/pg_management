<?php

namespace App\Providers;

use App\Logging\ModuleChannelRegistrar;
use App\Services\EnvManifest\EnvManifest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            EnvManifest::class,
            fn () => new EnvManifest(config_path('env-manifest'))
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setDynamicDatabaseByDomain();
        ModuleChannelRegistrar::register();
    }

    private function setDynamicDatabaseByDomain()
    {
        if (app()->environment('testing')) {
            return;
        }

        $request = request();

        if (! $request) {
            return;
        }

        $domain = $request->getHost();
        $databaseConfig = $this->getDatabaseConfigForDomain($domain);

        if ($databaseConfig) {
            $this->createAndSetDynamicConnection($databaseConfig);
        }
    }

    private function getDatabaseConfigForDomain($domain)
    {
        // USE_LOCAL_DB env variable takes priority over domain-based switching
        $useLocalDb = filter_var(env('USE_LOCAL_DB', false), FILTER_VALIDATE_BOOLEAN);

        if ($useLocalDb) {
            return config('database.connections.mariadb_local');
        }

        // If DB_CONNECTION is explicitly set to a non-mariadb driver (e.g. pgsql on Render),
        // use that connection directly instead of domain-based switching.
        $connection = env('DB_CONNECTION');
        if ($connection && $connection !== 'mariadb' && $connection !== 'mysql') {
            return config("database.connections.{$connection}");
        }

        // Domain to database mapping - fallback when USE_LOCAL_DB is false
        if ($domain == config('business.live_app_domain')) {
            return config('database.connections.mariadb');
        }

        return config('database.connections.mariadb_local');
    }

    /**
     * Track the last connection to avoid unnecessary purge on every request
     */
    private static ?string $lastConnectionName = null;

    private function createAndSetDynamicConnection($config)
    {
        $connectionName = $config['driver'];

        // Skip if the connection hasn't changed since last request
        if (static::$lastConnectionName === $connectionName && config('database.default') === $connectionName) {
            return;
        }

        // Set the connection in Laravel's config
        Config::set("database.connections.{$connectionName}", $config);

        // Set this as the default connection
        Config::set('database.default', $connectionName);

        // Purge any existing connections to ensure fresh connection
        DB::purge($connectionName);

        static::$lastConnectionName = $connectionName;
    }
}
