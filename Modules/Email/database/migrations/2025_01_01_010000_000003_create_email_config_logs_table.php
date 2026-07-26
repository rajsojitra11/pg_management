<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_config_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_config_id');
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
            $table->index('email_config_id');
            $table->index('user_id');
            $table->index('created_by');
            $table->index('activity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_config_logs');
    }
};
