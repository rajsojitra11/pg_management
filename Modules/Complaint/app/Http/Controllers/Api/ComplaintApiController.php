<?php

namespace Modules\Complaint\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Complaint\Http\Requests\StoreComplaintRequest;
use Modules\Complaint\Http\Requests\UpdateComplaintRequest;
use Modules\Complaint\Models\Complaint;
use Modules\Service\Models\Service;

class ComplaintApiController extends Controller
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
        $user = auth()->user();
        $query = Complaint::with('pg', 'room', 'category', 'service', 'createdBy');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('complaint_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $complaints = $query->orderByDesc('created_at')->paginate((int) request('per_page', 10));

        $data = $complaints->map(fn ($c) => [
            'public_id' => $c->public_id,
            'complaint_no' => $c->complaint_no,
            'pg_id' => (string) $c->pg?->id,
            'pg_name' => $c->pg?->pg_name,
            'room_id' => (string) $c->room?->id,
            'room_no' => $c->room?->room_no,
            'service_category_id' => (string) $c->category?->id,
            'service_category_name' => $c->category?->service_category_name,
            'service_id' => (string) $c->service?->id,
            'service_name' => $c->service?->service_name,
            'complaint_date' => $c->complaint_date?->toDateString(),
            'note' => $c->note,
            'status' => $c->status,
            'created_by' => $c->createdBy?->name,
            'created_at' => $c->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Complaint::with('pg', 'room', 'category', 'service', 'createdBy')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $c = $query->first();
            if (! is_null($c)) {
                return response()->json([
                    'data' => [
                        'public_id' => $c->public_id,
                        'complaint_no' => $c->complaint_no,
                        'pg_id' => (string) $c->pg?->id,
                        'pg_name' => $c->pg?->pg_name,
                        'room_id' => (string) $c->room?->id,
                        'room_no' => $c->room?->room_no,
                        'service_category_id' => (string) $c->category?->id,
                        'service_category_name' => $c->category?->service_category_name,
                        'service_id' => (string) $c->service?->id,
                        'service_name' => $c->service?->service_name,
                        'complaint_date' => $c->complaint_date?->toDateString(),
                        'note' => $c->note,
                        'status' => $c->status,
                        'created_by' => $c->createdBy?->name,
                        'created_at' => $c->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Complaint not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreComplaintRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['complaint_no'] = $this->generateComplaintNo();
            $data['created_by'] = auth()->id();
            $complaint = Complaint::create($data);
            $complaint->load('pg', 'room', 'category', 'service');

            DB::commit();

            return response()->json([
                'data' => [
                    'public_id' => $complaint->public_id,
                    'complaint_no' => $complaint->complaint_no,
                    'pg_id' => (string) $complaint->pg?->id,
                    'pg_name' => $complaint->pg?->pg_name,
                    'room_id' => (string) $complaint->room?->id,
                    'room_no' => $complaint->room?->room_no,
                    'service_category_id' => (string) $complaint->category?->id,
                    'service_category_name' => $complaint->category?->service_category_name,
                    'service_id' => (string) $complaint->service?->id,
                    'service_name' => $complaint->service?->service_name,
                    'complaint_date' => $complaint->complaint_date?->toDateString(),
                    'note' => $complaint->note,
                    'status' => $complaint->status,
                    'created_at' => $complaint->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function update(UpdateComplaintRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = Complaint::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $complaint = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $complaint->update($data);
            $complaint->load('pg', 'room', 'category', 'service');

            DB::commit();

            return response()->json([
                'data' => [
                    'public_id' => $complaint->public_id,
                    'complaint_no' => $complaint->complaint_no,
                    'pg_id' => (string) $complaint->pg?->id,
                    'pg_name' => $complaint->pg?->pg_name,
                    'room_id' => (string) $complaint->room?->id,
                    'room_no' => $complaint->room?->room_no,
                    'service_category_id' => (string) $complaint->category?->id,
                    'service_category_name' => $complaint->category?->service_category_name,
                    'service_id' => (string) $complaint->service?->id,
                    'service_name' => $complaint->service?->service_name,
                    'complaint_date' => $complaint->complaint_date?->toDateString(),
                    'note' => $complaint->note,
                    'status' => $complaint->status,
                    'created_at' => $complaint->created_at?->toIso8601String(),
                ],
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $query = Complaint::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $complaint = $query->firstOrFail();
            $complaint->update(['deleted_by' => auth()->id()]);
            $complaint->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function servicesByCategory(Request $request)
    {
        $categoryId = $request->query('category_id');
        if (! $categoryId) {
            return response()->json(['data' => []]);
        }

        $services = Service::select('id', 'service_name')
            ->where('service_category_id', $categoryId)
            ->where('status', 'active')
            ->orderBy('service_name')
            ->get()
            ->map(fn ($s) => ['id' => (string) $s->id, 'label' => $s->service_name]);

        return response()->json(['data' => $services]);
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
