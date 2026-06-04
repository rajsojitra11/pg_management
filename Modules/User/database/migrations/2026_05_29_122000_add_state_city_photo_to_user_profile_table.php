<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profile', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profile', 'state_id')) {
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete()->after('address');
            }
            if (! Schema::hasColumn('user_profile', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete()->after('state_id');
            }
            if (! Schema::hasColumn('user_profile', 'profile_photo')) {
                $table->string('profile_photo', 255)->nullable()->after('city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profile', function (Blueprint $table) {
            if (Schema::hasColumn('user_profile', 'profile_photo')) {
                $table->dropColumn('profile_photo');
            }
            if (Schema::hasColumn('user_profile', 'city_id')) {
                $table->dropForeign(['city_id']);
                $table->dropColumn('city_id');
            }
            if (Schema::hasColumn('user_profile', 'state_id')) {
                $table->dropForeign(['state_id']);
                $table->dropColumn('state_id');
            }
        });
    }
};
