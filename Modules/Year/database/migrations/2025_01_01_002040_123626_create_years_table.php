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
        Schema::create('years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('full_short')->nullable()->comment('YYYY-YY');
            $table->string('short_full')->nullable()->comment('YY-YYYY');
            $table->string('short_short')->nullable()->comment('YY-YY');
            $table->string('full_full')->nullable()->comment('YYYY-YYYY');
            $table->string('short')->nullable()->comment('YY');
            $table->string('full')->nullable()->comment('YYYY');
            $table->boolean('set_default')->default(1)->comment('1 default set year, 0 for other year');
            $table->timestamps();
            defaultMigration($table);
        });

        // Set default timestamps if ENABLE_HISTORICAL_DATA_ENTRY is enabled
        setDefaultTimestampsForTable('years');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('years');
    }
};
