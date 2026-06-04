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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile')->after('name');
            }

            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->after('mobile');
            }

            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }
        });

        // Add composite unique indexes in a separate call to ensure columns exist
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username') && Schema::hasColumn('users', 'deleted_at')) {
                if (! $this->hasIndex('users', 'users_username_deleted_at_unique')) {
                    $table->unique(['username', 'deleted_at']);
                }
            }

            if (Schema::hasColumn('users', 'mobile') && Schema::hasColumn('users', 'deleted_at')) {
                if (! $this->hasIndex('users', 'users_mobile_deleted_at_unique')) {
                    $table->unique(['mobile', 'deleted_at']);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'users_username_deleted_at_unique')) {
                $table->dropUnique('users_username_deleted_at_unique');
            }

            if ($this->hasIndex('users', 'users_mobile_deleted_at_unique')) {
                $table->dropUnique('users_mobile_deleted_at_unique');
            }

            if (Schema::hasColumn('users', 'mobile')) {
                $table->dropColumn('mobile');
            }

            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable(false)->change();
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};
