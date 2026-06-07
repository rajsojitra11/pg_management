<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pg_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->unsignedBigInteger('pg_id');
            $table->unsignedBigInteger('category_id');
            $table->string('room_no');
            $table->integer('bed_capacity')->nullable();
            $table->decimal('rent_amount', 10, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_rooms');
    }
};
