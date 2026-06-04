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
        if (! Schema::hasTable('user_profile')) {
            Schema::create('user_profile', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('firstname');
                $table->string('lastname');
                $table->date('date_of_birth')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('created_by')->comment('user id')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->unsignedBigInteger('updated_by')->comment('user id')->nullable();
                $table->foreign('updated_by')->references('id')->on('users');
                $table->unsignedBigInteger('deleted_by')->comment('user id')->nullable();
                $table->foreign('deleted_by')->references('id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profile');
    }
};
