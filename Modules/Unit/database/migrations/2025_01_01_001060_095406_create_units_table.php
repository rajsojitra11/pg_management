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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->double('unit_value', 8, 2)->nullable();
            $table->timestamps();
            defaultMigration($table);
        });

        // Set default timestamps if ENABLE_HISTORICAL_DATA_ENTRY is enabled
        setDefaultTimestampsForTable('units');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
