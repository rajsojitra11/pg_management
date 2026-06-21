<?php

namespace Modules\Complaint\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Complaint\Http\Requests\DeleteComplaintRequest;
use Modules\Complaint\Http\Requests\StoreComplaintRequest;
use Modules\Complaint\Http\Requests\UpdateComplaintRequest;
use Modules\Complaint\Models\Complaint;
use Modules\PgManagement\Models\PgManagement;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Yajra\DataTables\DataTables;

class ComplaintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:complaint-list|complaint-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:complaint-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:complaint-show', ['only' => ['show']]);
        $this->middleware('permission:complaint-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:complaint-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $user = auth()->user();
            $query = Complaint::with('pg', 'room', 'category', 'service', 'createdBy')
                ->select('id', 'public_id', 'complaint_no', 'pg_id', 'room_id', 'service_category_id', 'service_id', 'complaint_date', 'note', 'status', 'created_by');

            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('pg_name', function ($row) {
                    return $row->pg?->pg_name ?? '—';
                })
                ->addColumn('room_no', function ($row) {
                    return $row->room?->room_no ?? '—';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category?->service_category_name ?? '—';
                })
                ->addColumn('service_name', function ($row) {
                    return $row->service?->service_name ?? '—';
                })
                ->addColumn('created_user', function ($row) {
                    return $row->createdBy?->name ?? '—';
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'complaint-show';
                    $edit = $flag ? 'complaint-edit' : '';
                    $delete = $flag ? 'complaint-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            $user = auth()->user();
            $query = PgManagement::select('id', 'pg_name')->where('status', 'active');

            if ($user->hasRole('Pg_Admin')) {
                $query->where('owner_id', $user->id);
            }

            $pgList = $query->get();
            $categories = ServiceCategory::select('id', 'service_category_name')
                ->where('status', 'active')
                ->orderBy('service_category_name')
                ->get();

            return view('complaint::index', compact('pgList', 'categories'));
        }
    }

    public function show($id)
    {
        try {
            $complaint = Complaint::with('pg', 'room', 'category', 'service')->byAnyKey($id)->first();
            if (! is_null($complaint)) {
                return response()->json(['status_code' => 200, 'message' => 'View complaint', 'result' => $complaint]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Complaint not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreComplaintRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['complaint_no'] = $this->generateComplaintNo();
            $data['created_by'] = auth()->id();
            Complaint::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('complaint::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $complaint = Complaint::with('pg', 'room', 'category', 'service')->byAnyKey($id)->first();
            if (! is_null($complaint)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit complaint', 'result' => $complaint]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Complaint not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateComplaintRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $complaint = Complaint::byAnyKey($id)->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $complaint->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('complaint::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteComplaintRequest $request, $id)
    {
        try {
            $complaint = Complaint::byAnyKey($id)->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $complaint->update($data);
            $complaint->delete();

            return response()->json(['status_code' => 200, 'message' => __('complaint::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function servicesByCategory(Request $request)
    {
        $categoryId = $request->query('category_id');
        if (! $categoryId) {
            return response()->json([]);
        }

        $services = Service::select('id', 'service_name')
            ->where('service_category_id', $categoryId)
            ->where('status', 'active')
            ->orderBy('service_name')
            ->get()
            ->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->service_name]);

        return response()->json($services);
    }

    public function create()
    {
        abort(404);
    }

    public function nextComplaintNo()
    {
        return response()->json(['complaint_no' => $this->generateComplaintNo()]);
    }

    private function generateComplaintNo(): string
    {
        $year = now()->format('Y');
        $prefix = $year;

        $last = Complaint::where('complaint_no', 'like', $prefix.'%')
            ->orderByDesc('complaint_no')
            ->value('complaint_no');

        if ($last) {
            $seq = (int) substr($last, 4) + 1;
        } else {
            $seq = 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
