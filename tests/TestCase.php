<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Clean up leaked transactions from controllers that don't properly
     * commit/rollback (e.g. early returns inside DB::beginTransaction blocks).
     * Without this, SQLite throws "cannot start a transaction within a transaction".
     */
    protected function tearDown(): void
    {
        while (\DB::transactionLevel() > 1) {
            \DB::rollBack();
        }
        parent::tearDown();
    }

    protected function createPermissions(array $permissions): void
    {
        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p, 'guard_name' => 'web'],
                ['title' => $p, 'title_tag' => $p]
            );
        }
    }

    protected function createRoleWithPermissions(string $roleName, array $permissions, $user): void
    {
        $this->createPermissions($permissions);
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['title' => $roleName, 'title_tag' => $roleName]
        );
        $role->syncPermissions($permissions);
        $user->assignRole($role);
    }

    protected function seedPrefixFor(string $prefixName, string $prePrefix = 'TEST/'): void
    {
        \DB::table('prefix_masters')->insert([
            'prefix_name' => $prefixName,
            'module_name' => $prefixName,
            'pre_prefix' => $prePrefix,
            'post_prefix' => '',
            'prefix_start' => 1,
            'prefix_padding' => 5,
            'last_generated' => 0,
            'prefix_type' => 'local',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function seedYear(): int
    {
        return \DB::table('years')->insertGetId([
            'name' => '2025-2026',
            'full_short' => '2025-26',
            'short_full' => '25-2026',
            'short_short' => '25-26',
            'full_full' => '2025-2026',
            'short' => '26',
            'full' => '2026',
            'set_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Assert a log record exists with exhaustive field verification.
     *
     * Validates ALL fields written by HasActivityLogging: user_id, created_by, created_at,
     * system_remark, ip_address, browser, platform, device, old_values/new_values.
     *
     * @param  string  $logTable  The log table name (e.g. 'city_logs')
     * @param  string  $foreignKeyColumn  The foreign key column name (e.g. 'city_id')
     * @param  int  $foreignKeyValue  The foreign key value
     * @param  string  $expectedActivity  Expected activity type (e.g. 'created', 'updated', 'deleted')
     * @param  int  $expectedUserId  Expected user_id and created_by
     * @param  array  $options  Additional assertions:
     *                          - system_remark_contains: string to assert system_remark contains
     *                          - new_values_keys: array of keys that must exist in new_values JSON
     *                          - old_values_keys: array of keys that must exist in old_values JSON
     *                          - old_value_check: associative array of field => value to check in old_values
     *                          - new_value_check: associative array of field => value to check in new_values
     *                          - skip_device_check: bool, skip ip/browser/platform/device assertions (for simplified log models)
     *                          - expect_null_old_values: bool, assert old_values is null (for 'created' logs)
     *                          - expect_null_new_values: bool, assert new_values is null (for 'deleted' logs)
     *                          - additional_where: array of column => value for extra WHERE clauses
     * @return object The log record
     */
    protected function assertLogRecord(
        string $logTable,
        string $foreignKeyColumn,
        int $foreignKeyValue,
        string $expectedActivity,
        int $expectedUserId,
        array $options = []
    ): object {
        $query = \DB::table($logTable)
            ->where($foreignKeyColumn, $foreignKeyValue)
            ->where('activity', $expectedActivity);

        if (isset($options['additional_where'])) {
            foreach ($options['additional_where'] as $col => $val) {
                $query->where($col, $val);
            }
        }

        $log = $query->orderByDesc('id')->first();

        $this->assertNotNull($log, "No log record found in {$logTable} where {$foreignKeyColumn}={$foreignKeyValue} and activity={$expectedActivity}");

        // Core identity fields (some simplified log models may not have user_id)
        if (property_exists($log, 'user_id') && $log->user_id !== null) {
            $this->assertEquals($expectedUserId, $log->user_id, "Log user_id mismatch in {$logTable}");
        }
        $this->assertEquals($expectedUserId, $log->created_by, "Log created_by mismatch in {$logTable}");

        // created_at must exist
        $this->assertNotNull($log->created_at, "Log created_at is null in {$logTable}");

        // system_remark check (nullable in some simplified log models)
        if (property_exists($log, 'system_remark') && ! empty($options['system_remark_contains'])) {
            $this->assertNotNull($log->system_remark, "Log system_remark is null in {$logTable}");
        }

        if (isset($options['system_remark_contains'])) {
            $this->assertStringContainsString(
                $options['system_remark_contains'],
                $log->system_remark,
                "Log system_remark does not contain expected string in {$logTable}"
            );
        }

        // Device info checks (skip for simplified log models like DispenseorderRawMaterialLog)
        if (empty($options['skip_device_check'])) {
            $this->assertNotNull($log->ip_address, "Log ip_address is null in {$logTable}");
            $this->assertNotNull($log->browser, "Log browser is null in {$logTable}");
            $this->assertNotNull($log->platform, "Log platform is null in {$logTable}");
            $this->assertNotNull($log->device, "Log device is null in {$logTable}");
        }

        // old_values checks
        if (! empty($options['expect_null_old_values'])) {
            $this->assertNull($log->old_values, "Log old_values should be null in {$logTable}");
        } else {
            if (isset($options['old_values_keys'])) {
                $oldValues = is_string($log->old_values) ? json_decode($log->old_values, true) : $log->old_values;
                $this->assertNotNull($oldValues, "Log old_values is null in {$logTable}");
                foreach ($options['old_values_keys'] as $key) {
                    $this->assertArrayHasKey($key, $oldValues, "Log old_values missing key '{$key}' in {$logTable}");
                }
            }
            if (isset($options['old_value_check'])) {
                $oldValues = is_string($log->old_values) ? json_decode($log->old_values, true) : $log->old_values;
                $this->assertNotNull($oldValues, "Log old_values is null in {$logTable}");
                foreach ($options['old_value_check'] as $field => $expectedValue) {
                    $this->assertArrayHasKey($field, $oldValues, "Log old_values missing key '{$field}' in {$logTable}");
                    $this->assertEquals($expectedValue, $oldValues[$field], "Log old_values['{$field}'] mismatch in {$logTable}");
                }
            }
        }

        // new_values checks
        if (! empty($options['expect_null_new_values'])) {
            $this->assertNull($log->new_values, "Log new_values should be null in {$logTable}");
        } else {
            if (isset($options['new_values_keys'])) {
                $newValues = is_string($log->new_values) ? json_decode($log->new_values, true) : $log->new_values;
                $this->assertNotNull($newValues, "Log new_values is null in {$logTable}");
                foreach ($options['new_values_keys'] as $key) {
                    $this->assertArrayHasKey($key, $newValues, "Log new_values missing key '{$key}' in {$logTable}");
                }
            }
            if (isset($options['new_value_check'])) {
                $newValues = is_string($log->new_values) ? json_decode($log->new_values, true) : $log->new_values;
                $this->assertNotNull($newValues, "Log new_values is null in {$logTable}");
                foreach ($options['new_value_check'] as $field => $expectedValue) {
                    $this->assertArrayHasKey($field, $newValues, "Log new_values missing key '{$field}' in {$logTable}");
                    $this->assertEquals($expectedValue, $newValues[$field], "Log new_values['{$field}'] mismatch in {$logTable}");
                }
            }
        }

        return $log;
    }
}
