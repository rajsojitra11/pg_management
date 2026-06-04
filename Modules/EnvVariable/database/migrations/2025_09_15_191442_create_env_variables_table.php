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
        Schema::create('env_variables', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255)->unique();
            $table->text('value')->nullable();
            $table->string('type', 50)->default('text');
            $table->json('options')->nullable();
            $table->string('category', 100)->nullable();
            $table->text('validation_rules')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->boolean('requires_restart')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['key', 'is_active']);
            $table->index(['is_active', 'created_at']);
            $table->index(['category']);
            $table->index(['type']);
            $table->index(['sort_order']);
            $table->index(['requires_restart']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('env_variables');
    }
};
