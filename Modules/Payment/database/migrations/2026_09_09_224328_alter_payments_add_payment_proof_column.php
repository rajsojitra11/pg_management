<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payments', 'payment_proof')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payments', 'payment_proof')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });
    }
};
