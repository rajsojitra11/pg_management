<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearStorage extends Command
{
    protected $signature = 'storage:clear {--confirm : Skip confirmation prompt}';

    protected $description = 'Clear all uploaded files from storage/app/public (tenant-id-proofs, profile-photos, noticeboard, payment-proofs)';

    public function handle(): int
    {
        $dirs = [
            'tenant-id-proofs',
            'profile-photos',
            'noticeboard',
            'payment-proofs',
        ];

        $base = storage_path('app/public');
        $totalRemoved = 0;

        foreach ($dirs as $dir) {
            $path = $base.'/'.$dir;
            if (! is_dir($path)) {
                continue;
            }
            $files = File::files($path);
            foreach ($files as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }
                File::delete($file->getPathname());
                $totalRemoved++;
            }
        }

        $this->info("Cleared {$totalRemoved} file(s) from storage.");

        return Command::SUCCESS;
    }
}
