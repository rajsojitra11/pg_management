<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pg_management', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('pg_name');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('mobile_no', 20)->nullable();
            $table->integer('total_block')->nullable();
            $table->integer('total_room')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_management');
    }
};
