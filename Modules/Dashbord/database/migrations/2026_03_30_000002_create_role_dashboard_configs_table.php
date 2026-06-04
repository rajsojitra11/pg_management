<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_dashboard_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('widget_id');
            $table->boolean('enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->enum('size', ['full', 'half', 'quarter'])->default('quarter');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('widget_id')->references('id')->on('dashboard_widgets')->onDelete('cascade');
            $table->unique(['role_id', 'widget_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_dashboard_configs');
    }
};
