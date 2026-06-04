<?php

declare(strict_types=1);

/**
 * REMARKS-FIELD-BATCH-001 — Stage 8 / T-024 cross-cutting Rule:
 *
 * "A regression test fails the build if any future blade reintroduces
 *  inline <textarea name='remark'>" (.feature lines 124..130).
 *
 * Architecture-level guard: no module blade may render its remarks textarea
 * inline. Every transactional create/edit blade must use the canonical
 * partial @include('partials-tw.remarks-field', [...]).
 *
 * Why a hand-rolled scan instead of arch()->expect():
 *   - Pest's `arch()` API targets PHP namespaces / classes / files, not
 *     blade content. We need a blade-content scan with multi-line regex
 *     support (Production/create.blade.php has its `<textarea` token and
 *     `name="remark"` on different lines — a single-line scan would miss
 *     it, which is exactly the AUDIT-RULE-GAP that motivated this feature).
 *   - The companion `php artisan ui:audit` rule (R-PROJ-006) currently
 *     uses a single-line regex too. This test is the multi-line backstop
 *     until ui:audit is patched in a follow-up batch.
 *
 * Allow-list (REMARKS-FIELD-BATCH-002 candidate, documented in
 * `features/REMARKS-FIELD-BATCH-001-...deps.md` §3b):
 *
 *   The 8 inline-textarea sites below are KNOWN out-of-scope for this
 *   feature. They will be migrated in a follow-up batch. Listing them
 *   here keeps the test green TODAY while still blocking any *new*
 *   regression. Each entry MUST include a justification, and entries
 *   MUST be removed once the follow-up batch ships.
 *
 *   The §3b list also contains read-only `show.blade.php` audit-display
 *   textareas (Product/show, Production/show, PurchaseReceive/show,
 *   Rawmaterial/show). Those are debatable — they are not transactional
 *   saves — and may warrant a future `partials-tw.remarks-field-readonly`
 *   partial. They are likewise allow-listed below pending that decision.
 */

/**
 * Allow-list of files KNOWN to still contain inline `<textarea name="remark">`.
 *
 * Each entry: blade-relative path → reason for being on the allow-list.
 *
 * EVERY entry on this list represents technical debt. The list MUST shrink
 * over time. Adding a new entry requires:
 *   1. A linked follow-up feature ID (e.g., REMARKS-FIELD-BATCH-002)
 *   2. A red-flag entry in `.claude/red-flags/registry.md`
 *   3. Reviewer sign-off
 */
const REMARKS_FIELD_OOS_ALLOWLIST = [
    // ── REMARKS-FIELD-BATCH-002 candidates (audit-rule scope gap §3b) ──
    'Modules/Formulation/resources/views/edit.blade.php' => 'BATCH-002 candidate — audit-rule scope gap (single-line regex but edit.blade.php not scanned)',
    'Modules/Product/resources/views/edit.blade.php' => 'BATCH-002 candidate — audit-rule scope gap',
    'Modules/Purchase/resources/views/edit.blade.php' => 'BATCH-002 candidate — audit-rule scope gap',
    'Modules/Rawmaterial/resources/views/edit.blade.php' => 'BATCH-002 candidate — audit-rule scope gap',
    'Modules/SalesOrder/resources/views/edit.blade.php' => 'BATCH-002 candidate — audit-rule scope gap',
    'Modules/Gatepass/resources/views/create.blade.php' => 'BATCH-002 candidate — audit-rule scope gap',
    'Modules/Gatepass/resources/views/edit.blade.php' => 'BATCH-002 candidate — audit-rule scope gap',
    'Modules/Testing/resources/views/index.blade.php' => 'BATCH-002 candidate — audit-rule scope gap (index-page inline textarea)',

    // ── Read-only audit-display show pages (potential remarks-field-readonly partial candidate) ──
    'Modules/Product/resources/views/show.blade.php' => 'Read-only audit display — pending partials-tw.remarks-field-readonly decision',
    'Modules/Production/resources/views/show.blade.php' => 'Read-only audit display — pending partials-tw.remarks-field-readonly decision',
    'Modules/PurchaseReceive/resources/views/show.blade.php' => 'Read-only audit display — pending partials-tw.remarks-field-readonly decision',
    'Modules/Rawmaterial/resources/views/show.blade.php' => 'Read-only audit display — pending partials-tw.remarks-field-readonly decision',

    // ── Pre-existing inline `<textarea name="remarks">` (plural form) sites ──
    // These use a DIFFERENT field name than the canonical `remark` migrated by
    // BATCH-001. They predate the canonicalization decision and are tracked as
    // BATCH-002 candidates. Field-rename "remarks" → canonical partial is
    // explicitly out of scope for BATCH-001 (deps.md §7.1).
    'Modules/MaterialIssue/resources/views/create.blade.php' => 'BATCH-002 candidate — pre-existing `name="remarks"` plural form; field-rename out of scope for BATCH-001',
    'Modules/MaterialIssue/resources/views/edit.blade.php' => 'BATCH-002 candidate — pre-existing `name="remarks"` plural form; field-rename out of scope for BATCH-001',
    'Modules/ExtraMaterialIssue/resources/views/create.blade.php' => 'BATCH-002 candidate — pre-existing `name="remarks"` plural form; field-rename out of scope for BATCH-001',
    'Modules/ExtraMaterialIssue/resources/views/edit.blade.php' => 'BATCH-002 candidate — pre-existing `name="remarks"` plural form; field-rename out of scope for BATCH-001',
];

dataset('moduleBlades', function () {
    // Resolve project root without going through base_path() — datasets
    // are resolved before the application is booted. We climb from this
    // file's location instead: tests/Feature/RemarksFieldArchTest.php
    // → project root is two levels up.
    $base = dirname(__DIR__, 2).'/Modules';
    if (! is_dir($base)) {
        return [];
    }

    $files = [];
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        // Only blade.php files under Modules/<Name>/resources/views/**
        if (! preg_match('#/Modules/[^/]+/resources/views/.+\.blade\.php$#', $path)) {
            continue;
        }
        // Make path relative to project root for human-readable test names
        $relative = substr($path, strpos($path, 'Modules/'));
        $files[$relative] = [$relative];
    }
    ksort($files);

    return $files;
});

it('module blade does not contain inline <textarea name="remark"> (R-PROJ-006 arch guard)', function (string $relativePath) {
    if (array_key_exists($relativePath, REMARKS_FIELD_OOS_ALLOWLIST)) {
        // Documented out-of-scope sibling — see REMARKS_FIELD_OOS_ALLOWLIST
        // header for the policy. The test passes for these files but the
        // entry MUST be removed when the follow-up batch ships.
        expect(REMARKS_FIELD_OOS_ALLOWLIST[$relativePath])->not->toBeEmpty();

        return;
    }

    // Use the project's actual file path (handles Windows \ vs Unix /).
    $absolute = base_path($relativePath);
    expect(file_exists($absolute))->toBeTrue("Blade not found: {$relativePath}");

    $source = file_get_contents($absolute);

    // Multi-line regex: <textarea ... name="remark"> where ... may span
    // multiple lines (the Production audit-miss case). The `s` flag makes
    // `.` match newlines. We bound the gap with a sane character cap to
    // prevent runaway across the whole file.
    $offenders = [];

    if (preg_match('/<textarea[^>]{0,400}\sname=["\']remark["\']/s', $source)) {
        $offenders[] = 'inline `<textarea ... name="remark">` (use @include(\'partials-tw.remarks-field\', [...]) instead)';
    }

    // Defensive — guard against future field-rename remark → remarks
    if (preg_match('/<textarea[^>]{0,400}\sname=["\']remarks["\']/s', $source)) {
        $offenders[] = 'inline `<textarea ... name="remarks">` (use @include(\'partials-tw.remarks-field\', [\'fieldName\' => \'remarks\']) instead)';
    }

    expect($offenders)->toBe(
        [],
        "{$relativePath} contains inline remarks textarea(s) that must be migrated to the canonical partial:\n  - "
            .implode("\n  - ", $offenders)
            ."\n\nFix: replace the inline <textarea> + <label> + error-<div> block with\n"
            ."  @include('partials-tw.remarks-field', [\n"
            ."      'type' => 'create' /* or 'update' / 'delete' */,\n"
            ."      'fieldName' => 'remark',\n"
            ."      'fieldId' => '<preserve_original_id>',\n"
            ."  ])\n"
            .'Or — if this is a documented exception — add the blade path to '
            .'REMARKS_FIELD_OOS_ALLOWLIST in tests/Feature/RemarksFieldArchTest.php with a one-line justification.'
    );
})->with('moduleBlades');

it('the OOS allow-list does not contain stale entries', function () {
    foreach (REMARKS_FIELD_OOS_ALLOWLIST as $relativePath => $reason) {
        $absolute = base_path($relativePath);
        expect(file_exists($absolute))->toBeTrue(
            "Stale entry in REMARKS_FIELD_OOS_ALLOWLIST: {$relativePath} no longer exists. "
            .'Remove it from the allow-list.'
        );
        expect($reason)->not->toBe('', "Allow-list entry for {$relativePath} must include a justification.");

        // The allow-list is only meaningful if the file STILL contains an
        // inline remarks textarea. If a file was migrated but its allow-list
        // entry wasn't pruned, the entry is stale.
        $source = file_get_contents($absolute);
        $hasInline = (bool) preg_match(
            '/<textarea[^>]{0,400}\sname=["\']remarks?["\']/s',
            $source
        );
        expect($hasInline)->toBeTrue(
            "Stale entry in REMARKS_FIELD_OOS_ALLOWLIST: {$relativePath} no longer contains an "
            .'inline remarks textarea — the migration appears to have been completed. '
            .'Remove it from the allow-list.'
        );
    }
});
