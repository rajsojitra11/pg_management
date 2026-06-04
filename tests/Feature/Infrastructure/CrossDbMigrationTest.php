<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRATION-CROSS-DB-001 — Stage 9 sanity test.
 *
 * Asserts the entire migration suite runs end-to-end on the testing connection
 * (SQLite :memory:, configured by phpunit.xml). This is the durable regression
 * guard for the cross-DB driver-guard pattern: any future migration that
 * introduces unguarded MariaDB-only DDL (e.g., `ALTER TABLE … MODIFY`,
 * `… ADD CONSTRAINT … CHECK`, `… CHANGE …`) will fail this test before
 * reaching production.
 *
 * Companion regression coverage:
 *   - LabMaterial: `chk_lm_standard_tier` CHECK previously raised on SQLite
 *     because the guard used strict `=== 'mysql'` (RF-2026-0011). Widened to
 *     `in_array(driver, ['mysql','mariadb'], true)` and the SQLite branch is a
 *     no-op (CHECK enforced via PHP Enum cast + FormRequest 'in:' rule).
 *   - ProcessStage: `ALTER TABLE … CHANGE …` previously raised
 *     "near 'CHANGE': syntax error" on SQLite. Now branches to
 *     Schema::table->renameColumn() on non-MySQL drivers.
 *
 * Per feature brief (.deps.md §6.1): one happy-path SQLite migration suite
 * run is enough verification. Do NOT add per-migration cross-DB tests.
 */
it('runs every migration cleanly on the testing SQLite connection', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite');

    $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

    expect($exitCode)->toBe(0)
        ->and(Schema::hasTable('lab_materials'))->toBeTrue()
        ->and(Schema::hasTable('process_route_stages'))->toBeTrue()
        ->and(Schema::hasColumn('process_route_stages', 'process_stage_id'))->toBeTrue()
        ->and(Schema::hasColumn('process_route_stages', 'unit_operation_id'))->toBeFalse()
        ->and(Schema::hasTable('client_specification_method_versions'))->toBeTrue();
});
