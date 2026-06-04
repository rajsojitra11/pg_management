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
        if (! Schema::hasTable('user_hierarchies')) {

            Schema::create('user_hierarchies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_id')->comment('user id')->nullable();
                $table->foreign('parent_id')->references('id')->on('users');
                $table->foreignId('user_id');
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
        Schema::dropIfExists('user_hierarchies');
    }
};
