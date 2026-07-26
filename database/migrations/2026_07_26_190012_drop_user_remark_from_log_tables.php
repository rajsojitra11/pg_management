<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $logTables = [
        'city_logs',
        'complaint_logs',
        'country_logs',
        'currency_logs',
        'email_config_logs',
        'email_template_logs',
        'env_variable_logs',
        'maintenance_logs',
        'menu_master_logs',
        'noticeboard_logs',
        'payment_logs',
        'pg_management_logs',
        'pg_room_category_logs',
        'pg_room_logs',
        'role_logs',
        'service_category_logs',
        'service_logs',
        'setting_logs',
        'state_logs',
        'subscription_logs',
        'tenant_logs',
        'unit_logs',
        'user_logs',
        'year_logs',
    ];

    public function up(): void
    {
        foreach ($this->logTables as $tableName) {
            if (Schema::hasColumn($tableName, 'user_remark')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('user_remark');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->logTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('user_remark')->nullable();
                });
            }
        }
    }
};
