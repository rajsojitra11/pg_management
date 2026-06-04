<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The Role model uses the HasPublicId trait, but the Spatie-generated
     * `roles` table was never given a `public_id` column (the bulk
     * add_public_id_* migrations skipped it). Add it and backfill existing rows.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'public_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->char('public_id', 26)->nullable()->unique()->after('id');
            });
        }

        foreach (DB::table('roles')->whereNull('public_id')->get() as $row) {
            DB::table('roles')->where('id', $row->id)->update(['public_id' => (string) Str::ulid()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('roles', 'public_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('public_id');
            });
        }
    }
};
