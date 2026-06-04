<?php

it('does not use raw HTML date inputs in any module view', function () {
    $matches = grepModuleViews('/type=("|\')(date|datetime-local)("|\')/i');

    expect($matches)
        ->toBeEmpty(
            "Module views must use the Flatpickr datetime input (class=\"flatpickr-datetime\")\n".
            "instead of raw <input type=\"date\"> / type=\"datetime-local\".\n\n".
            "Offending lines:\n".formatHits($matches)
        );
});

function grepModuleViews(string $regex): array
{
    $modulesDir = base_path('Modules');

    if (! is_dir($modulesDir)) {
        return [];
    }

    $hits = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS));

    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }
        if (! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $contents = @file_get_contents($file->getPathname());
        if ($contents === false) {
            continue;
        }

        foreach (preg_split('/\R/', $contents) as $i => $line) {
            if (preg_match($regex, $line)) {
                $hits[] = $file->getPathname().':'.($i + 1).' — '.trim($line);
            }
        }
    }

    return $hits;
}

function formatHits(array $hits): string
{
    return implode("\n", array_map(fn ($h) => '  '.$h, array_slice($hits, 0, 20)));
}
