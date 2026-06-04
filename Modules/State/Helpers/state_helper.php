<?php

use Modules\City\Models\City;

function state_delete_check($id)
{
    if (! class_exists('Modules\City\Models\City')) {
        return true;
    }

    $city = City::where('state_id', $id)->count();
    if ($city > 0) {
        return false;
    } else {
        return true;
    }
}
