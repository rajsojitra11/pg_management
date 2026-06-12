<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Http\Requests\DeletePaymentRequest;
use Modules\Payment\Http\Requests\StorePaymentRequest;
use Modules\Payment\Http\Requests\UpdatePaymentRequest;
use Modules\Payment\Models\Payment;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payment-list|payment-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:payment-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:payment-show', ['only' => ['show']]);
        $this->middleware('permission:payment-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:payment-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $user = auth()->user();
            $query = Payment::with('tenant', 'pg', 'room')
                ->select('id', 'public_id', 'tenant_id', 'pg_id', 'room_id', 'payment_date', 'amount', 'payment_method', 'reference_no', 'status');

            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn($q) => $q->where('owner_id', $user->id));
            }

            if ($search = trim((string) request('filter_search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('amount', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            }

            if ($status = request('filter_status')) {
                $query->where('status', $status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('tenant_name', function ($row) {
                    return $row->tenant?->name ?? '—';
                })
                ->addColumn('pg_name', function ($row) {
                    return $row->pg?->pg_name ?? '—';
                })
                ->addColumn('room_no', function ($row) {
                    return $row->room?->room_no ?? '—';
                })
                ->editColumn('status', function ($row) {
                    if ($row->status === 'paid') {
                        return '<span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 border border-green-200">Paid</span>';
                    }
                    if ($row->status === 'pending') {
                        return '<span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">Pending</span>';
                    }
                    if ($row->status === 'refunded') {
                        return '<span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 border border-red-200">Refunded</span>';
                    }
                    return $row->status ? ucfirst($row->status) : '';
                })
                ->addColumn('action', function ($row) {
                    $show = 'payment-show';
                    $edit = 'payment-edit';
                    $delete = 'payment-delete';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view('payment::index');
    }

    public function create()
    {
        return view('payment::create');
    }

    public function store(StorePaymentRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            Payment::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('payment::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Payment::with('tenant', 'pg', 'room')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn($q) => $q->where('owner_id', $user->id));
            }
            $payment = $query->first();
            if (! is_null($payment)) {
                return response()->json(['status_code' => 200, 'message' => 'View Payment', 'result' => $payment]);
            }

            return response()->json(['status_code' => 404, 'message' => 'Payment not found.']);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();
            $query = Payment::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn($q) => $q->where('owner_id', $user->id));
            }
            $payment = $query->first();
            if (! is_null($payment)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit Payment', 'result' => $payment]);
            }

            return response()->json(['status_code' => 404, 'message' => 'Payment not found.']);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdatePaymentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = Payment::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn($q) => $q->where('owner_id', $user->id));
            }
            $payment = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $payment->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('payment::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeletePaymentRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = Payment::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn($q) => $q->where('owner_id', $user->id));
            }
            $payment = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $payment->update($data);
            $payment->delete();

            return response()->json(['status_code' => 200, 'message' => __('payment::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
