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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id')->comment('country id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries');
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->enum('is_ut', ['Y', 'N'])->default('N')->comment('Union Territory');
            $table->timestamps();
            defaultMigration($table);
        });

        // Set default timestamps if ENABLE_HISTORICAL_DATA_ENTRY is enabled
        setDefaultTimestampsForTable('states');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
