<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('status', 'verified');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('verified', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('verified', 'status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 20)->default('paid')->change();
        });
    }
};
