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
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('status');
                $table->rememberToken();
                $table->timestamps();
                $table->timestamp('deleted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->foreign('created_by')->references('id')->on('users');
                $table->foreign('updated_by')->references('id')->on('users');
                $table->foreign('deleted_by')->references('id')->on('users');

                $table->unique(['email', 'deleted_at']);
            });
        } else {
            // Ensure all required columns exist (in case a basic table was created externally)
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'status')) {
                    $table->string('status')->default('active')->after('password');
                }
                if (! Schema::hasColumn('users', 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable();
                }
                if (! Schema::hasColumn('users', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->foreign('created_by')->references('id')->on('users');
                }
                if (! Schema::hasColumn('users', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                    $table->foreign('updated_by')->references('id')->on('users');
                }
                if (! Schema::hasColumn('users', 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable();
                    $table->foreign('deleted_by')->references('id')->on('users');
                }
            });

            // Add composite unique index if not exists
            if (Schema::hasColumn('users', 'email') && Schema::hasColumn('users', 'deleted_at')) {
                $indexes = Schema::getIndexes('users');
                $hasEmailIndex = collect($indexes)->contains(fn ($i) => $i['name'] === 'users_email_deleted_at_unique');
                if (! $hasEmailIndex) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->unique(['email', 'deleted_at']);
                    });
                }
            }
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
