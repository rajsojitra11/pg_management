<?php

declare(strict_types=1);

/**
 * LOOKUP-CONSOLIDATION-001 — Stage 9 / T-033 cross-cutting Rule:
 * "regression test prevents controllers from re-introducing direct lookup
 *  queries" (.feature line 243-249).
 *
 * Architecture-level guard: no module controller may issue a forbidden
 * lookup-model query pattern (`<Model>::select(...)`, `::where(...)`,
 * `::orderBy(...)`, `::pluck(...)`, `::all()`, `::get()`) inside its
 * `create()` / `edit()` / dropdown-bearing `index()` body.
 *
 * Why a hand-rolled test instead of the Pest 4 `arch()->toUse()` syntax:
 *   - `arch()->toUse([...])` checks file-level imports only. Many partial
 *     exceptions (e.g. Customer keeps Country/State imports for the
 *     locations() multi-row grid) need method-level granularity.
 *   - We need to scan every module controller and run the same regex against
 *     the create()/edit() bodies, while allowing a documented exception list.
 *
 * Whitelist (per .feature lines 220-237 + Stage 8b deferral notes in tasks
 * file lines 156-192):
 *   1. LookupController itself — it IS the lookup source.
 *   2. Specification, Reagent, ProcessRoute — documented eager-load
 *      exceptions (low-cardinality masters; AJAX overkill).
 *   3. Customer.locations() / Supplier.locations() — multi-row grid uses
 *      eager-loads. We only scan create()/edit() in those controllers.
 *   4. Formulation create/edit — DEFERRED with documented R-PROJ-016 comment
 *      (multi-table grid; missing exclude_formulated/use_as_not filters).
 *   5. Dashbord settings — server-rendered tabs (NOT a dropdown).
 *   6. Lookup-source modules' own controllers (CountryController,
 *      StateController, CityController, UnitController, CurrencyController,
 *      TransportController, YearController, LocationController,
 *      StorageconditionController, RawMaterialCategoryController,
 *      ProductCategoryController) — they own the lookup table; their own
 *      CRUD index/create/edit doesn't go through LookupController.
 *
 * Why static-source rather than runtime: the `chk_lm_standard_tier CHECK`
 * SQLite migration in lab_materials prevents `RefreshDatabase` (tracked
 * separately as MIGRATION-CROSS-DB-001 — out of scope here).
 */

/**
 * Models that MUST be served via canonical LookupController endpoints.
 * Cross-module direct queries against any of these inside create()/edit() are
 * R-PROJ-016 violations (anchor RF-2026-005).
 */
const LOOKUP_FORBIDDEN_MODELS = [
    'City',
    'Country',
    'Currency',
    'MenuMaster',
    'State',
    'Unit',
    'Year',
];

/**
 * Controllers exempted from the rule. Each entry: full path → reason.
 * If you need to add an entry, document the justification in this list.
 */
const LOOKUP_EXEMPT_CONTROLLERS = [
    // ── Lookup-source modules: their own CRUD controllers query their own model ──
    'Modules/City/app/Http/Controllers/CityController.php' => 'Owns the city table — own CRUD',
    'Modules/Country/app/Http/Controllers/CountryController.php' => 'Owns the country table — own CRUD',
    'Modules/Currency/app/Http/Controllers/CurrencyController.php' => 'Owns the currency table — own CRUD',
    'Modules/MenuMaster/app/Http/Controllers/MenuMasterController.php' => 'Owns the menu_masters table — own CRUD',
    'Modules/State/app/Http/Controllers/StateController.php' => 'Owns the state table — own CRUD',
    'Modules/Unit/app/Http/Controllers/UnitController.php' => 'Owns the unit table — own CRUD',
    'Modules/Year/app/Http/Controllers/YearController.php' => 'Owns the year table — own CRUD',

    // ── Sample transactional CRUD — uses Unit lookup, which is allowed for the entity-owner module ──
    'Modules/Item/app/Http/Controllers/ItemController.php' => 'Owns the items table — own CRUD; Unit is referenced via lookup.units in the dropdown',

    // ── Auth / system controllers (not dropdown surfaces) ──
    'Modules/Dashbord/app/Http/Controllers/DashbordController.php' => 'Server-rendered tabs/panels — not a dropdown UI',
    'Modules/EnvVariable/app/Http/Controllers/EnvVariableController.php' => 'Env variable admin — not a CRUD dropdown surface',
    'Modules/Installer/app/Http/Controllers/InstallController.php' => 'Bootstrap installer — runs before LookupController is reachable',
    'Modules/Installer/app/Http/Controllers/InstallerController.php' => 'Bootstrap installer — same as above',
    'Modules/Login/app/Http/Controllers/AuthenticatedSessionController.php' => 'Uses Year for fiscal-year defaulting at login — not a dropdown',
    'Modules/Login/app/Http/Controllers/LoginController.php' => 'Login flow — not a CRUD',
    'Modules/Role/app/Http/Controllers/RoleController.php' => 'Spatie role admin — own CRUD',
    'Modules/Setting/app/Http/Controllers/SettingController.php' => 'Settings admin — uses Country/State/City via the Setting->country relation, not direct queries',
    'Modules/User/app/Http/Controllers/UserController.php' => 'Owns the users table — own CRUD',
];

dataset('moduleControllers', function () {
    // Resolve project root without going through base_path() — datasets
    // are resolved before the application is booted, so the helper is
    // unavailable. We climb from this file's location instead:
    // tests/Feature/ArchTest.php → project root is two levels up.
    $base = dirname(__DIR__, 2).'/Modules';
    if (! is_dir($base)) {
        return [];
    }

    $files = [];
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        // Only Modules/<Name>/app/Http/Controllers/*.php
        if (! preg_match('#/Modules/[^/]+/app/Http/Controllers/[^/]+Controller\.php$#', $path)) {
            continue;
        }
        // Make path relative to project root for human-readable test names
        $relative = substr($path, strpos($path, 'Modules/'));
        $files[$relative] = [$relative];
    }
    ksort($files);

    return $files;
});

it('controller does not query forbidden lookup models inside create()/edit() (R-PROJ-016 arch guard)', function (string $relativePath) {
    if (array_key_exists($relativePath, LOOKUP_EXEMPT_CONTROLLERS)) {
        // Documented exception — skip this controller. The exemption rationale
        // is captured in LOOKUP_EXEMPT_CONTROLLERS at the top of this file.
        expect(LOOKUP_EXEMPT_CONTROLLERS[$relativePath])->not->toBeEmpty();

        return;
    }

    $absolute = base_path($relativePath);
    expect(file_exists($absolute))->toBeTrue("Controller not found: {$relativePath}");

    $source = file_get_contents($absolute);

    // Find every method body of interest. The minimal set the .feature
    // pins to is create() / edit(); we add index() because the rule
    // applies "to the dropdown-bearing branch of index()" too.
    $methodsOfInterest = ['create', 'edit', 'index'];
    $offenders = [];

    foreach ($methodsOfInterest as $method) {
        $body = extractMethodBodyForArch($source, $method);
        if ($body === null) {
            continue; // Not every controller has every method.
        }

        foreach (LOOKUP_FORBIDDEN_MODELS as $model) {
            // Skip if the controller belongs to the same module as the model
            // (intra-module queries are not lookup violations — they're
            // local CRUD / data lookups). The path is the source of truth:
            // Modules/<Name>/... and the model name aligns when applicable.
            if (str_contains($relativePath, "Modules/{$model}/")) {
                continue;
            }

            $patterns = [
                "{$model}::select",
                "{$model}::where",
                "{$model}::orderBy",
                "{$model}::pluck",
                "{$model}::all(",
                "{$model}::get(",
            ];

            foreach ($patterns as $needle) {
                if (str_contains($body, $needle)) {
                    $offenders[] = "{$method}() contains `{$needle}`";
                }
            }
        }
    }

    expect($offenders)->toBe(
        [],
        "{$relativePath} has direct lookup-model queries that must be routed through LookupController:\n  - "
            .implode("\n  - ", $offenders)
            ."\n\nFix: replace with `data-ajax-lookup` blade hook + the matching canonical endpoint, "
            .'or — if this is a documented exception — add the controller path to '
            .'LOOKUP_EXEMPT_CONTROLLERS in tests/ArchTest.php with a one-line justification.'
    );
})->with('moduleControllers');

it('the exemption list does not contain stale entries', function () {
    foreach (LOOKUP_EXEMPT_CONTROLLERS as $relativePath => $reason) {
        $absolute = base_path($relativePath);
        expect(file_exists($absolute))->toBeTrue(
            "Stale entry in LOOKUP_EXEMPT_CONTROLLERS: {$relativePath} no longer exists. "
            .'Remove it from the exemption list.'
        );
        expect($reason)->not->toBe('', "Exemption for {$relativePath} must include a justification.");
    }
});

/**
 * Extract a method body by name. Naive but reliable: regex match on signature,
 * then walk braces.
 */
function extractMethodBodyForArch(string $source, string $methodName): ?string
{
    $pattern = '/(?:public|protected|private)\s+(?:static\s+)?function\s+'.preg_quote($methodName, '/').'\s*\(/';
    if (! preg_match($pattern, $source, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $start = $m[0][1];
    $bracePos = strpos($source, '{', $start);
    if ($bracePos === false) {
        return null;
    }

    $depth = 0;
    $len = strlen($source);
    $bodyStart = $bracePos + 1;
    $i = $bracePos;
    while ($i < $len) {
        $c = $source[$i];
        if ($c === '{') {
            $depth++;
        } elseif ($c === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($source, $bodyStart, $i - $bodyStart);
            }
        }
        $i++;
    }

    return null;
}
