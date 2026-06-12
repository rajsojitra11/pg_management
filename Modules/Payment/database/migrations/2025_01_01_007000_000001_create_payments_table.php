<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();

            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('pg_id')->constrained('pg_management');
            $table->foreignId('room_id')->constrained('pg_rooms');

            $table->date('payment_date');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50); // Cash, Bank Transfer, Cheque, UPI, Other
            $table->string('reference_no', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('paid'); // paid, pending, refunded

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
