<?php

use Modules\Customer\Models\Customerlocation;
use Modules\Supplier\Models\Supplierlocation;

function city_delete_check($id)
{
    if (! class_exists('Modules\Supplier\Models\Supplierlocation') && ! class_exists('Modules\Customer\Models\Customerlocation')) {
        return true;
    }

    $supplier = 0;
    $customer = 0;

    if (class_exists('Modules\Supplier\Models\Supplierlocation')) {
        $supplier = Supplierlocation::where('city_id', $id)->count();
    }

    if (class_exists('Modules\Customer\Models\Customerlocation')) {
        $customer = Customerlocation::where('city_id', $id)->count();
    }

    if ($supplier > 0 || $customer > 0) {
        return false;
    } else {
        return true;
    }
}
