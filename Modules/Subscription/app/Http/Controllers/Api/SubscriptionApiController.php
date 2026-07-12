<?php

namespace Modules\Subscription\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\Http\Requests\StoreSubscriptionRequest;
use Modules\Subscription\Http\Requests\UpdateSubscriptionRequest;
use Modules\Subscription\Models\Subscription;

class SubscriptionApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:subscription-list|subscription-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:subscription-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:subscription-show', ['only' => ['show']]);
        $this->middleware('permission:subscription-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:subscription-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $query = Subscription::query();

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('subscriber_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = request('filter_status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = request('filter_payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        $subscriptions = $query->orderByDesc('created_at')->paginate((int) request('per_page', 10));

        $data = $subscriptions->map(fn ($s) => [
            'id' => (string) $s->id,
            'public_id' => $s->public_id,
            'subscriber_name' => $s->subscriber_name,
            'email' => $s->email,
            'phone' => $s->phone,
            'plan_type' => $s->plan_type,
            'start_date' => $s->start_date?->toDateString(),
            'end_date' => $s->end_date?->toDateString(),
            'status' => $s->status,
            'amount' => (float) $s->amount,
            'payment_status' => $s->payment_status,
            'created_at' => $s->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $subscription = Subscription::byAnyKey($id)->first();
            if (! is_null($subscription)) {
                return response()->json([
                    'data' => [
                        'id' => (string) $subscription->id,
                        'public_id' => $subscription->public_id,
                        'subscriber_name' => $subscription->subscriber_name,
                        'email' => $subscription->email,
                        'phone' => $subscription->phone,
                        'plan_type' => $subscription->plan_type,
                        'start_date' => $subscription->start_date?->toDateString(),
                        'end_date' => $subscription->end_date?->toDateString(),
                        'status' => $subscription->status,
                        'amount' => (float) $subscription->amount,
                        'payment_status' => $subscription->payment_status,
                        'created_at' => $subscription->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Subscription not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreSubscriptionRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $subscription = Subscription::create($data);

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $subscription->id,
                    'public_id' => $subscription->public_id,
                    'subscriber_name' => $subscription->subscriber_name,
                    'email' => $subscription->email,
                    'phone' => $subscription->phone,
                    'plan_type' => $subscription->plan_type,
                    'start_date' => $subscription->start_date?->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                    'status' => $subscription->status,
                    'amount' => (float) $subscription->amount,
                    'payment_status' => $subscription->payment_status,
                    'created_at' => $subscription->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function edit($id)
    {
        try {
            $subscription = Subscription::byAnyKey($id)->first();
            if (! is_null($subscription)) {
                return response()->json([
                    'data' => [
                        'id' => (string) $subscription->id,
                        'public_id' => $subscription->public_id,
                        'subscriber_name' => $subscription->subscriber_name,
                        'email' => $subscription->email,
                        'phone' => $subscription->phone,
                        'plan_type' => $subscription->plan_type,
                        'start_date' => $subscription->start_date?->toDateString(),
                        'end_date' => $subscription->end_date?->toDateString(),
                        'status' => $subscription->status,
                        'amount' => (float) $subscription->amount,
                        'payment_status' => $subscription->payment_status,
                        'created_at' => $subscription->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Subscription not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function update(UpdateSubscriptionRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $subscription = Subscription::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $subscription->update($data);
            $subscription->refresh();

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $subscription->id,
                    'public_id' => $subscription->public_id,
                    'subscriber_name' => $subscription->subscriber_name,
                    'email' => $subscription->email,
                    'phone' => $subscription->phone,
                    'plan_type' => $subscription->plan_type,
                    'start_date' => $subscription->start_date?->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                    'status' => $subscription->status,
                    'amount' => (float) $subscription->amount,
                    'payment_status' => $subscription->payment_status,
                    'created_at' => $subscription->created_at?->toIso8601String(),
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
            $subscription = Subscription::findByAnyKeyOrFail($id);
            $subscription->update(['deleted_by' => auth()->id()]);
            $subscription->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
