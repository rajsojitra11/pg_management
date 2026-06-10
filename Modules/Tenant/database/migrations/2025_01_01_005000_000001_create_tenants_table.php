<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active');

            // Step 1: PG & Personal Details
            $table->foreignId('pg_id')->nullable()->constrained('pg_management')->nullOnDelete();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('bed_no', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('occupation', 100)->nullable();

            // Step 2: Stay & Payment Details
            $table->date('checkin_date')->nullable();
            $table->date('expected_checkout_date')->nullable();
            $table->decimal('monthly_rent', 10, 2)->nullable();
            $table->decimal('security_deposit', 10, 2)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('id_proof_type', 50)->nullable();
            $table->string('id_proof_number', 100)->nullable();
            $table->string('id_proof_file')->nullable();

            // Step 3: Emergency Contact & Permanent Address
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_relation', 100)->nullable();
            $table->string('emergency_contact_number', 20)->nullable();
            $table->foreignId('permanent_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('permanent_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->text('permanent_address')->nullable();
            $table->text('additional_notes')->nullable();

            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
