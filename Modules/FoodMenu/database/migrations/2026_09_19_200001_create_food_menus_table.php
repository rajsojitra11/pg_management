<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_menus', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pg_id');
            $table->string('menu_type')->default('weekly');
            $table->string('title');
            $table->date('week_start_date')->nullable();
            $table->date('special_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_menus');
    }
};
