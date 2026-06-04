<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profile', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profile', 'address')) {
                $table->text('address')->nullable()->after('date_of_birth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profile', function (Blueprint $table) {
            if (Schema::hasColumn('user_profile', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
