<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_configs', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->unsignedBigInteger('pg_id');
            $table->foreign('pg_id')->references('id')->on('pg_management')->onDelete('cascade');
            $table->string('sender_email');
            $table->string('sender_name')->nullable();
            $table->string('subject_prefix')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_configs');
    }
};
