<?php

use Modules\State\Models\State;

function country_delete_check($id)
{
    if (! class_exists('Modules\State\Models\State')) {
        return true;
    }

    $state = State::where('country_id', $id)->count();
    if ($state > 0) {
        return false;
    } else {
        return true;
    }
}
