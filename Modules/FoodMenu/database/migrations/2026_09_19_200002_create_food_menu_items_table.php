<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->unsignedBigInteger('food_menu_id');
            $table->string('day')->nullable();
            $table->string('meal_time');
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            defaultMigration($table);

            $table->index(['food_menu_id', 'meal_time']);
            $table->index(['food_menu_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_menu_items');
    }
};
