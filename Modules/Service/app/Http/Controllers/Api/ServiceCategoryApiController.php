<?php

namespace Modules\Service\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Service\Models\ServiceCategory;

class ServiceCategoryApiController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::where('status', 'active')
            ->orderBy('service_category_name')
            ->get(['id', 'service_category_name']);

        $data = $categories->map(fn ($c) => [
            'id' => $c->id,
            'service_category_name' => $c->service_category_name,
        ]);

        return response()->json(['data' => $data]);
    }
}
