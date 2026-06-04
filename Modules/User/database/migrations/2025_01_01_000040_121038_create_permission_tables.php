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
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (empty($tableNames)) {
            throw new Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }
        if ($teams && empty($columnNames['team_foreign_key'] ?? null)) {
            throw new Exception('Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        if (! Schema::hasTable('permissions')) {

            Schema::create($tableNames['permissions'], function (Blueprint $table) {
                // $table->engine('InnoDB');
                $table->bigIncrements('id'); // permission id
                $table->string('title');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
                $table->string('title_tag');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
                $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
                $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
                $table->timestamps();
                defaultMigration($table);

                $table->unique(['name', 'guard_name']);
            });
        } else {
            // If permissions table already exists, check and add missing columns
            Schema::table($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
                if (! Schema::hasColumn($tableNames['permissions'], 'title')) {
                    $table->string('title');
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'title_tag')) {
                    $table->string('title_tag');
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'name')) {
                    $table->string('name');
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'guard_name')) {
                    $table->string('guard_name');
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'created_at')) {
                    $table->timestamps();
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'deleted_at')) {
                    $table->softDeletes();
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'created_by')) {
                    $table->unsignedBigInteger('created_by')->comment('user id')->nullable();
                    $table->foreign('created_by')->references('id')->on('users');
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->comment('user id')->nullable();
                    $table->foreign('updated_by')->references('id')->on('users');
                }
                if (! Schema::hasColumn($tableNames['permissions'], 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->comment('user id')->nullable();
                    $table->foreign('deleted_by')->references('id')->on('users');
                }
            });

            // Add unique constraint if it doesn't exist
            if (! collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($tableNames['permissions']))->keys()->contains('permissions_name_guard_name_unique')) {
                Schema::table($tableNames['permissions'], function (Blueprint $table) {
                    $table->unique(['name', 'guard_name']);
                });
            }
        }
        if (! Schema::hasTable('roles')) {

            Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
                // $table->engine('InnoDB');
                $table->bigIncrements('id'); // role id
                if ($teams || config('permission.testing')) {
                    // permission.testing is a fix for sqlite testing
                    $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                    $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
                }
                $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
                $table->string('title')->nullable(); // For MyISAM use string('title', 225); // (or 166 for InnoDB with Redundant/Compact row format)
                $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
                $table->timestamps();
                defaultMigration($table);
                if ($teams || config('permission.testing')) {
                    $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        } else {
            // If roles table already exists, check and add missing columns
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames, $tableNames) {
                if (($teams || config('permission.testing')) && ! Schema::hasColumn($tableNames['roles'], $columnNames['team_foreign_key'])) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                    $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
                }
                if (! Schema::hasColumn($tableNames['roles'], 'name')) {
                    $table->string('name');
                }
                if (! Schema::hasColumn($tableNames['roles'], 'title')) {
                    $table->string('title')->nullable();
                }
                if (! Schema::hasColumn($tableNames['roles'], 'guard_name')) {
                    $table->string('guard_name');
                }
                if (! Schema::hasColumn($tableNames['roles'], 'created_at')) {
                    $table->timestamps();
                }
                if (! Schema::hasColumn($tableNames['roles'], 'deleted_at')) {
                    $table->softDeletes();
                }
                if (! Schema::hasColumn($tableNames['roles'], 'created_by')) {
                    $table->unsignedBigInteger('created_by')->comment('user id')->nullable();
                    $table->foreign('created_by')->references('id')->on('users');
                }
                if (! Schema::hasColumn($tableNames['roles'], 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->comment('user id')->nullable();
                    $table->foreign('updated_by')->references('id')->on('users');
                }
                if (! Schema::hasColumn($tableNames['roles'], 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->comment('user id')->nullable();
                    $table->foreign('deleted_by')->references('id')->on('users');
                }
            });

            // Add unique constraint if it doesn't exist
            $indexName = ($teams || config('permission.testing')) ? 'roles_'.$columnNames['team_foreign_key'].'_name_guard_name_unique' : 'roles_name_guard_name_unique';
            if (! collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($tableNames['roles']))->keys()->contains($indexName)) {
                Schema::table($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
                    if ($teams || config('permission.testing')) {
                        $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
                    } else {
                        $table->unique(['name', 'guard_name']);
                    }
                });
            }
        }

        if (! Schema::hasTable('model_has_permissions')) {

            Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
                $table->unsignedBigInteger($pivotPermission);

                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($pivotPermission)
                    ->references('id') // permission id
                    ->on($tableNames['permissions']);
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                    $table->primary(
                        [$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_permissions_permission_model_type_primary'
                    );
                } else {
                    $table->primary(
                        [$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_permissions_permission_model_type_primary'
                    );
                }
            });
        }
        if (! Schema::hasTable('model_has_roles')) {

            Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
                $table->unsignedBigInteger($pivotRole);

                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($pivotRole)
                    ->references('id') // role id
                    ->on($tableNames['roles']);
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                    $table->primary(
                        [$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_roles_role_model_type_primary'
                    );
                } else {
                    $table->primary(
                        [$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                        'model_has_roles_role_model_type_primary'
                    );
                }
            });
        }
        if (! Schema::hasTable('role_has_permissions')) {

            Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);

                $table->foreign($pivotPermission)
                    ->references('id') // permission id
                    ->on($tableNames['permissions']);

                $table->foreign($pivotRole)
                    ->references('id') // role id
                    ->on($tableNames['roles']);

                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
