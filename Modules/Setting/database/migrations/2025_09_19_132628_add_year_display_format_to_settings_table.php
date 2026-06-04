<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('year_display_format', [
                'full_short', 'short_full', 'short_short',
                'full_full', 'short', 'full',
            ])->default('full_short')->after('city_id')
                ->comment('Year display format throughout the system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('year_display_format');
        });
    }
};
