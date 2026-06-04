<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds ULID public_id column to tables whose models use HasPublicId trait
 * but were created before public_id was included in the boilerplate schema.
 *
 * These tables were missed by the original add_public_id_to_main_entity_tables
 * migration because they are module tables created later (2026-05-20+) without
 * the column, while the model trait was applied globally.
 */
return new class extends Migration
{
    private array $tables = [
        'job_sizes',
        'machines',
        'paper_gsm',
        'post_press',
        'printing',
        'sheet_sizes',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'public_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->char('public_id', 26)->nullable()->after('id');
            });
        }

        // Backfill existing rows
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

        // Unique index (nullable column)
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                try {
                    $t->unique('public_id', "{$table}_public_id_unique");
                } catch (Throwable $e) {
                    // Index already exists
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
