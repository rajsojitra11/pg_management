<?php

use App\Providers\AppServiceProvider;
use App\Providers\ModuleMigrationServiceProvider;
use Yajra\DataTables\DataTablesServiceProvider;

return [
    AppServiceProvider::class,
    ModuleMigrationServiceProvider::class,
    DataTablesServiceProvider::class,

];
