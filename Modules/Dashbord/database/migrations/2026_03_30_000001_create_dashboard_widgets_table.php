<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->enum('type', ['kpi', 'financial', 'chart', 'table']);
            $table->string('section');
            $table->string('icon')->nullable();
            $table->string('icon_bg')->nullable();
            $table->string('icon_color')->nullable();
            $table->string('permission')->nullable();
            $table->string('data_endpoint')->nullable();
            $table->boolean('default_enabled')->default(true);
            $table->integer('default_order')->default(0);
            $table->string('description')->nullable();
            $table->json('config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
