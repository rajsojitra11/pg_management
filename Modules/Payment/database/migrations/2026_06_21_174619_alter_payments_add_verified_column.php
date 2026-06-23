<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'status')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('status', 'verified');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payments', 'verified')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('verified', 'status');
        });
    }
};
