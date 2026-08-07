<?php

namespace Modules\Payment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Http\Requests\StorePaymentRequest;
use Modules\Payment\Http\Requests\UpdatePaymentRequest;
use Modules\Payment\Models\Payment;
use Modules\Tenant\Models\Tenant;

class PaymentApiController extends Controller
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
        $user = auth()->user();
        $query = Payment::with('tenant', 'pg', 'room');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('amount', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($tenantId = request('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($verified = request('verified')) {
            $query->where('verified', $verified);
        }

        $payments = $query->orderByDesc('created_at')->paginate((int) request('per_page', 10));

        $data = $payments->map(fn ($p) => [
            'id' => (string) $p->id,
            'public_id' => $p->public_id,
            'tenant_id' => (string) $p->tenant_id,
            'tenant_name' => $p->tenant?->name,
            'tenant_checkin_date' => $p->tenant?->checkin_date?->toDateString(),
            'pg_id' => (string) $p->pg?->id,
            'pg_name' => $p->pg?->pg_name,
            'room_id' => (string) $p->room?->id,
            'room_no' => $p->room?->room_no,
            'payment_date' => $p->payment_date?->toDateString(),
            'amount' => (float) $p->amount,
            'payment_method' => $p->payment_method,
            'reference_no' => $p->reference_no,
            'remarks' => $p->remarks,
            'verified' => $p->verified,
            'created_at' => $p->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Payment::with('tenant', 'pg', 'room')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $p = $query->first();
            if (! is_null($p)) {
                return response()->json([
                    'data' => [
                        'id' => (string) $p->id,
                        'public_id' => $p->public_id,
                        'tenant_id' => (string) $p->tenant_id,
                        'tenant_name' => $p->tenant?->name,
                        'tenant_checkin_date' => $p->tenant?->checkin_date?->toDateString(),
                        'pg_id' => (string) $p->pg?->id,
                        'pg_name' => $p->pg?->pg_name,
                        'room_id' => (string) $p->room?->id,
                        'room_no' => $p->room?->room_no,
                        'payment_date' => $p->payment_date?->toDateString(),
                        'amount' => (float) $p->amount,
                        'payment_method' => $p->payment_method,
                        'reference_no' => $p->reference_no,
                        'remarks' => $p->remarks,
                        'verified' => $p->verified,
                        'created_at' => $p->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Payment not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StorePaymentRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $payment = Payment::create($data);
            $payment->load('tenant', 'pg', 'room');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $payment->id,
                    'public_id' => $payment->public_id,
                    'tenant_id' => (string) $payment->tenant_id,
                    'tenant_name' => $payment->tenant?->name,
                    'tenant_checkin_date' => $payment->tenant?->checkin_date?->toDateString(),
                    'pg_id' => (string) $payment->pg?->id,
                    'pg_name' => $payment->pg?->pg_name,
                    'room_id' => (string) $payment->room?->id,
                    'room_no' => $payment->room?->room_no,
                    'payment_date' => $payment->payment_date?->toDateString(),
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'reference_no' => $payment->reference_no,
                    'remarks' => $payment->remarks,
                    'verified' => $payment->verified,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function update(UpdatePaymentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = Payment::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $payment = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $payment->update($data);
            $payment->load('tenant', 'pg', 'room');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $payment->id,
                    'public_id' => $payment->public_id,
                    'tenant_id' => (string) $payment->tenant_id,
                    'tenant_name' => $payment->tenant?->name,
                    'tenant_checkin_date' => $payment->tenant?->checkin_date?->toDateString(),
                    'pg_id' => (string) $payment->pg?->id,
                    'pg_name' => $payment->pg?->pg_name,
                    'room_id' => (string) $payment->room?->id,
                    'room_no' => $payment->room?->room_no,
                    'payment_date' => $payment->payment_date?->toDateString(),
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'reference_no' => $payment->reference_no,
                    'remarks' => $payment->remarks,
                    'verified' => $payment->verified,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ],
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function toggleVerified($id)
    {
        try {
            $user = auth()->user();
            $query = Payment::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $payment = $query->firstOrFail();

            $payment->verified = $payment->verified === 'verified' ? 'pending' : 'verified';
            $payment->updated_by = auth()->id();
            $payment->save();

            return response()->json([
                'verified' => $payment->verified,
                'message' => $payment->verified === 'verified'
                    ? 'Payment marked as verified.'
                    : 'Payment marked as pending.',
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function pendingPayments()
    {
        $user = auth()->user();

        $query = Tenant::with('pg:id,public_id,pg_name', 'room:id,public_id,room_no')
            ->select('id', 'public_id', 'name', 'pg_id', 'room_id', 'checkin_date', 'monthly_rent')
            ->withMax('payments as last_payment_date', 'payment_date')
            ->whereNotNull('checkin_date')
            ->where('checkin_date', '<=', now()->subMonth());

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        // A tenant is pending when no payment covers the current billing month.
        // The billing cutoff is check-in date advanced by the whole months
        // elapsed since check-in. Computed in PHP so it stays portable across
        // MySQL, PostgreSQL and SQLite (raw DATE_ADD/TIMESTAMPDIFF is MySQL-only).
        $tenants = $query->orderBy('name')->get()
            ->filter(fn ($t) => $t->checkin_date !== null
                && (
                    $t->last_payment_date === null
                    || Carbon::parse($t->last_payment_date)->lt($t->checkin_date->copy()->addMonths($t->checkin_date->diffInMonths(now())))
                )
            );

        $data = $tenants->map(fn ($t) => [
            'id' => (string) $t->id,
            'public_id' => $t->public_id,
            'name' => $t->name,
            'pg_id' => (string) $t->pg?->id,
            'pg_name' => $t->pg?->pg_name,
            'room_id' => (string) $t->room?->id,
            'room_no' => $t->room?->room_no,
            'checkin_date' => $t->checkin_date?->toDateString(),
            'monthly_rent' => (float) $t->monthly_rent,
            'days_elapsed' => $t->last_payment_date
                ? $t->checkin_date->copy()->addMonths($t->checkin_date->diffInMonths(Carbon::parse($t->last_payment_date)) + 1)->diffInDays(now())
                : ($t->checkin_date ? $t->checkin_date->copy()->addMonths($t->checkin_date->diffInMonths(now()))->diffInDays(now()) : 0),
        ]);

        return response()->json(['data' => $data]);
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $query = Payment::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $payment = $query->firstOrFail();
            $payment->update(['deleted_by' => auth()->id()]);
            $payment->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
