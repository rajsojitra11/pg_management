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
        Schema::create('menu_masters', function (Blueprint $table) {
            $table->id();
            $table->string('menu_icon')->nullable();
            $table->string('menu_title')->nullable();
            $table->string('menu_route')->nullable();
            $table->string('module_name')->nullable();
            $table->string('order_display', 50); // Zero-padded: "001.001.001"
            $table->string('display_order', 50)->nullable(); // Human readable: "1.1.1"
            $table->string('if_can')->nullable();
            $table->boolean('is_main_menu')->default(0);
            $table->string('parent_id')->nullable();
            $table->timestamps();
            $table->index('order_display', 'idx_menu_masters_order_display');
            $table->index(['parent_id', 'order_display'], 'idx_menu_masters_parent_order');
            $table->index('is_main_menu', 'idx_menu_masters_is_main_menu');
            $table->index('module_name', 'idx_menu_masters_module_name');

            // Add index for soft deletes if using SoftDeletes trait
            $table->index('deleted_at', 'idx_menu_masters_deleted_at');
            defaultMigration($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_masters');
    }
};
