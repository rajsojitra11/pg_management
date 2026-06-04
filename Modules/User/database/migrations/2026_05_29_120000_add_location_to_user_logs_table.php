<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->string('location', 255)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
