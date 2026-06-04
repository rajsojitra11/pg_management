<?php

use Modules\Product\Models\Product;

function unit_delete_check($id)
{
    if (! class_exists('Modules\Product\Models\Product')) {
        return true;
    }

    $product = Product::where('unit_id', $id)->count();
    if ($product > 0) {
        return false;
    } else {
        return true;
    }
}
