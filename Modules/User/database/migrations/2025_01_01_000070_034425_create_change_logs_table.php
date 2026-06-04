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
        if (! Schema::hasTable('change_logs')) {

            Schema::create('change_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('action');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('guard')->default('web');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
