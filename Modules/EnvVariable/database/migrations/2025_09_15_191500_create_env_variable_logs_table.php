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
        Schema::create('env_variable_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('env_variable_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by');
            $table->string('activity', 50);
            $table->text('user_remark');
            $table->text('system_remark');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 100)->nullable();
            $table->string('platform', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->timestamp('created_at');

            // NO FOREIGN KEY CONSTRAINTS - Logs are immutable audit records
            $table->index(['env_variable_id', 'created_at']);
            $table->index(['user_id', 'activity']);
            $table->index(['activity', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('env_variable_logs');
    }
};
