<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('name', 100)->unique();
            $table->string('subject');
            $table->longText('body');
            $table->text('placeholders')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            defaultMigration($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
