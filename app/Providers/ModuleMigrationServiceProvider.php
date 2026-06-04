<?php

namespace App\Providers;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;

class ModuleMigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Load migrations from ALL modules (enabled AND disabled)
     * to ensure database schema integrity regardless of module status.
     */
    public function boot(): void
    {
        $this->app->resolving(Migrator::class, function (Migrator $migrator) {
            $migrationPath = config('modules.paths.generator.migration.path', 'database/migrations');

            // Load migrations from ALL modules (enabled AND disabled)
            collect(Module::all())->each(function ($module) use ($migrationPath, $migrator) {
                $migrator->path($module->getExtraPath($migrationPath));
            });
        });
    }
}
