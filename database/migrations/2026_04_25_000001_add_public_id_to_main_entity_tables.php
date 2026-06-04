<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds a ULID `public_id` column to all main entity tables for URL/API
 * addressing. PK and FKs remain BIGINT (fast joins, no schema-level disruption).
 *
 * Excluded from this rollout:
 * - All `*_logs` tables (append-only audit, never URL-routed)
 * - Composite-PK tables (unit_gravities, product_gravities, raw_material_gravities)
 * - Internal config tables (dashboard_widgets, role_dashboard_configs, user_dashboard_configs)
 * - Immutable history tables (productprinthistories, rawmaterialprinthistories)
 * - User 1:1 sub-tables (user_profiles, user_hierarchies)
 *
 * The column is added nullable and backfilled with Str::ulid() per existing row.
 * It stays nullable at the DB level so that test fixtures using raw
 * `DB::table()->insert([...])` (which bypass Eloquent) don't blow up. Production
 * inserts always go through Eloquent + the HasPublicId trait, which populates
 * the value via the `creating` event. Routes that look up by public_id will
 * naturally not match null rows — those legacy/fixture rows are reachable via
 * the legacy /{numeric_id} 301-redirect path.
 */
return new class extends Migration
{
    /**
     * Tables that get a public_id column.
     *
     * Boilerplate set: auth core + lookups + sample Item module.
     * Add new tables here as your project's transactional modules ship.
     *
     * Note: `roles` table excluded — Spatie's package owns the inserts and
     * bypasses the HasPublicId boot listener. Roles aren't URL-routed publicly;
     * they're admin-managed via the standard Spatie tooling.
     */
    private array $tables = [
        // Lookups
        'cities',
        'countries',
        'currencies',
        'states',
        'units',
        'years',

        // System
        'env_variables',
        'menu_masters',
        'settings',

        // Auth
        'users',

        // Sample CRUD
        'items',

        // Transactional modules
        'vendors',
    ];

    public function up(): void
    {
        // Step 1 — add nullable column (skip if already present)
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'public_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->char('public_id', 26)->nullable()->after('id');
            });
        }

        // Step 2 — backfill existing rows with ULIDs (chunked, per-row update)
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }
            DB::table($table)
                ->whereNull('public_id')
                ->orderBy('id')
                ->chunkById(1000, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['public_id' => (string) Str::ulid()]);
                    }
                });
        }

        // Step 3 — add unique index (column stays nullable; see header comment)
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                try {
                    $t->unique('public_id', "{$table}_public_id_unique");
                } catch (Throwable $e) {
                    // Index already exists — ignore
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                try {
                    $t->dropUnique("{$table}_public_id_unique");
                } catch (Throwable $e) {
                    // ignore
                }
                $t->dropColumn('public_id');
            });
        }
    }
};
