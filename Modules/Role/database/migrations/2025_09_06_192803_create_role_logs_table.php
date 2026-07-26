<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by');
            $table->string('activity', 50);
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
            $table->index(['role_id', 'created_at']);
            $table->index(['user_id', 'activity']);
            $table->index(['activity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_logs');
    }
};
