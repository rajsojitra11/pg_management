<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable()->after('designation');
            $table->unsignedBigInteger('head_id')->nullable()->after('manager_id');

            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('head_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['head_id']);
            $table->dropColumn(['manager_id', 'head_id']);
        });
    }
};
