<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dashboard_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('widget_id');
            $table->boolean('enabled')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('widget_id')->references('id')->on('dashboard_widgets')->onDelete('cascade');
            $table->unique(['user_id', 'widget_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_configs');
    }
};
