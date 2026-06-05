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
        if (Schema::hasTable('user_profile') && ! Schema::hasColumn('user_profile', 'parent_id')) {
            Schema::table('user_profile', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->after('user_id')->comment('user id')->nullable();
                $table->foreign('parent_id')->references('id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profile', function (Blueprint $table) {
            //
        });
    }
};
