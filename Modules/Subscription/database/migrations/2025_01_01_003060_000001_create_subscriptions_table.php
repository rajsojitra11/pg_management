<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('subscriber_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('plan_type')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_status')->default('pending');
            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
