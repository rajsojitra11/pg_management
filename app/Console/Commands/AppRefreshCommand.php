<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppRefreshCommand extends Command
{
    protected $signature = 'app:refresh
                            {--key : Also regenerate the APP_KEY}';

    protected $description = 'Clear all caches, sessions, and stale data. Run after APP_KEY regeneration or environment change.';

    public function handle(): int
    {
        $this->components->info('Refreshing application...');

        // 1. Regenerate APP_KEY if requested
        if ($this->option('key')) {
            $this->call('key:generate', ['--force' => true]);
        }

        // 2. Clear all framework caches FIRST so stale config doesn't
        //    interfere with database operations below
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('event:clear');

        // 3. Clear bootstrap cache files (packages, services, modules)
        $cacheFiles = [
            base_path('bootstrap/cache/packages.php'),
            base_path('bootstrap/cache/services.php'),
            base_path('bootstrap/cache/modules.php'),
        ];

        foreach ($cacheFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->components->task('Clear bootstrap cache files');

        // 4. Truncate cache table — cache:clear only removes keys matching
        //    the current prefix. Stale entries from a previous project or
        //    with a different CACHE_PREFIX survive cache:clear.
        if (Schema::hasTable('cache')) {
            DB::table('cache')->truncate();
            $this->components->task('Truncate cache table (removes all prefixes)');
        }

        // 5. Truncate sessions (encrypted with old key, unreadable after key change)
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->truncate();
            $this->components->task('Truncate sessions table');
        }

        // 6. Re-cache for performance
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');

        $this->newLine();
        $this->components->info('Application refreshed successfully. All users will need to re-login.');

        return self::SUCCESS;
    }
}
