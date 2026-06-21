<?php

namespace Modules\Service\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Service\Http\Requests\DeleteServiceRequest;
use Modules\Service\Http\Requests\StoreServiceRequest;
use Modules\Service\Http\Requests\UpdateServiceRequest;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Yajra\DataTables\DataTables;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:service-list|service-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:service-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:service-show', ['only' => ['show']]);
        $this->middleware('permission:service-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:service-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Service::with('category')
                ->select('id', 'public_id', 'service_category_id', 'service_name', 'status');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('category_name', function ($row) {
                    return $row->category?->service_category_name ?? '—';
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'service-show';
                    $edit = $flag ? 'service-edit' : '';
                    $delete = $flag ? 'service-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            $categories = ServiceCategory::select('id', 'service_category_name')
                ->where('status', 'active')
                ->orderBy('service_category_name')
                ->get();

            return view('service::service.index', compact('categories'));
        }
    }

    public function show($id)
    {
        try {
            $service = Service::with('category')->byAnyKey($id)->first();
            if (! is_null($service)) {
                return response()->json(['status_code' => 200, 'message' => 'View service', 'result' => $service]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Service not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreServiceRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->has('services') && is_array($request->input('services'))) {
                $serviceCategoryId = $data['service_category_id'];
                foreach ($data['services'] as $serviceData) {
                    Service::create([
                        'service_category_id' => $serviceCategoryId,
                        'service_name' => $serviceData['service_name'],
                        'status' => $serviceData['status'] ?? 'active',
                        'created_by' => auth()->id(),
                    ]);
                }
            } else {
                $data['created_by'] = auth()->id();
                Service::create($data);
            }

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
            $service = Service::with('category')->byAnyKey($id)->first();
            if (! is_null($service)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit service', 'result' => $service]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Service not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateServiceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $service = Service::byAnyKey($id)->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $service->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('service::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteServiceRequest $request, $id)
    {
        try {
            $service = Service::byAnyKey($id)->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $service->update($data);
            $service->delete();

            return response()->json(['status_code' => 200, 'message' => __('service::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
