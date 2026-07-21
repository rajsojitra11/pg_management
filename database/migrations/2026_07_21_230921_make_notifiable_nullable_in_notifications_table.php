<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notifiable_type')->nullable()->change();
            $table->string('notifiable_id', 36)->nullable()->change();
            $table->text('data')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notifiable_type')->nullable(false)->change();
            $table->string('notifiable_id', 36)->nullable(false)->change();
            $table->text('data')->nullable(false)->change();
        });
    }
};
