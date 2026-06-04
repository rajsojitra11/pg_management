<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;

/**
 * LOOKUP-CONSOLIDATION-001 — Stage 9 / T-033 cross-cutting Rule:
 * "Authorization is uniform across lookup endpoints" (.feature lines 260-267).
 *
 * Regression-guard: every route whose name starts with `lookup.` MUST sit
 * inside the `auth` middleware group. If a future refactor accidentally
 * registers a lookup endpoint outside the `Route::middleware(['auth'])` block
 * — e.g. by adding a new route at the top of routes/web.php — this test
 * fails and the offending route name(s) are reported back.
 *
 * Why it doesn't use RefreshDatabase / DB:
 * The `chk_lm_standard_tier` SQLite CHECK-constraint migration in lab_materials
 * fails on :memory: SQLite (tracked separately as MIGRATION-CROSS-DB-001).
 * Route-table inspection needs zero database access, so we sidestep the issue.
 */
it('every lookup.* route requires auth middleware', function () {
    $offenders = collect(Route::getRoutes())
        ->filter(fn (RouteInstance $r) => str_starts_with((string) $r->getName(), 'lookup.'))
        ->reject(fn (RouteInstance $r) => in_array('auth', $r->gatherMiddleware(), true))
        ->map(fn (RouteInstance $r) => $r->getName())
        ->values()
        ->all();

    expect($offenders)->toBe(
        [],
        'Lookup routes missing the `auth` middleware: '.implode(', ', $offenders)
            .'. Every lookup.* route must be registered inside the Route::middleware([\'auth\'])'
            .' group in routes/web.php (R-PROJ-016 / .feature Rule "Authorization is uniform across lookup endpoints").'
    );
});

it('the canonical lookup endpoints are all registered', function () {
    // Sanity check — fail loudly if Stage 8a deletes a route by accident.
    // List drawn from routes/web.php:59-84.
    $expected = [
        'lookup.products',
        'lookup.rawmaterials',
        'lookup.formulations',
        'lookup.customers',
        'lookup.process-stages',
        'lookup.specifications',
        'lookup.suppliers',
        'lookup.units',
        'lookup.storage-conditions',
        // Surface expansion (Stage 8a)
        'lookup.business-associates',
        'lookup.client-products',
        'lookup.countries',
        'lookup.states',
        'lookup.cities',
        'lookup.currencies',
        'lookup.transports',
        'lookup.raw-material-categories',
        'lookup.roles',
        'lookup.active-users',
        'lookup.years',
        'lookup.prefix-masters',
        'lookup.material-types',
        'lookup.lab-material-categories',
    ];

    $registered = collect(Route::getRoutes())
        ->map(fn (RouteInstance $r) => $r->getName())
        ->filter(fn ($n) => is_string($n) && str_starts_with($n, 'lookup.'))
        ->values()
        ->all();

    $missing = array_values(array_diff($expected, $registered));
    expect($missing)->toBe([], 'Lookup routes not registered: '.implode(', ', $missing));
});
