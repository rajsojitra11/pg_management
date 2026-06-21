<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('maintenance_no', 20)->unique();
            $table->unsignedBigInteger('complaint_id');
            $table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('cascade');
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('proof')->nullable();
            $table->text('description')->nullable();
            $table->date('maintenance_date');
            $table->string('status')->default('pending');
            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
