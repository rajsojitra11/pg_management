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
        Schema::create('year_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->nullable();
            $table->foreignId('user_id');
            $table->string('activity');

            $table->text('system_remark');

            // Data snapshots for audit trail
            $table->json('old_values')->nullable()->comment('Complete record state before change');
            $table->json('new_values')->nullable()->comment('Complete record state after change');
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('device')->nullable();
            $table->text('platform')->nullable();
            $table->text('browser')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Performance indexes
            $table->index(['year_id', 'activity', 'created_at']);
            $table->index('activity');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('year_logs');
    }
};
