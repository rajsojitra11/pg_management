<?php

namespace Modules\Service\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Service\Http\Requests\DeleteServiceCategoryRequest;
use Modules\Service\Http\Requests\StoreServiceCategoryRequest;
use Modules\Service\Http\Requests\UpdateServiceCategoryRequest;
use Modules\Service\Models\ServiceCategory;
use Yajra\DataTables\DataTables;

class ServiceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:service-category-list|service-category-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:service-category-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:service-category-show', ['only' => ['show']]);
        $this->middleware('permission:service-category-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:service-category-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = ServiceCategory::select('id', 'public_id', 'service_category_name', 'status');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = '';
                    $edit = $flag ? 'service-category-edit' : '';
                    $delete = $flag ? 'service-category-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            return view('service::category.index');
        }
    }

    public function show($id)
    {
        try {
            $category = ServiceCategory::byAnyKey($id)->first();
            if (! is_null($category)) {
                return response()->json(['status_code' => 200, 'message' => 'View category', 'result' => $category]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Category not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreServiceCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            ServiceCategory::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('service::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $category = ServiceCategory::byAnyKey($id)->first();
            if (! is_null($category)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit category', 'result' => $category]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Category not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateServiceCategoryRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $category = ServiceCategory::byAnyKey($id)->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $category->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('service::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteServiceCategoryRequest $request, $id)
    {
        try {
            $category = ServiceCategory::byAnyKey($id)->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $category->update($data);
            $category->delete();

            return response()->json(['status_code' => 200, 'message' => __('service::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
