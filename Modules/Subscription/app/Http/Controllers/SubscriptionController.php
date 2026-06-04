<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\Http\Requests\DeleteSubscriptionRequest;
use Modules\Subscription\Http\Requests\StoreSubscriptionRequest;
use Modules\Subscription\Http\Requests\UpdateSubscriptionRequest;
use Modules\Subscription\Models\Subscription;
use Modules\User\Models\User;
use Yajra\DataTables\DataTables;

class SubscriptionController extends Controller
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
        if (request()->ajax()) {
            return DataTables::of(Subscription::select('id', 'public_id', 'subscriber_name', 'email', 'phone', 'plan_type', 'start_date', 'end_date', 'status', 'amount', 'payment_status'))
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'subscription-show';
                    $edit = true ? 'subscription-edit' : '';
                    $delete = $flag ? 'subscription-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->editColumn('status', function ($row) {
                    if ($row->status === 'active') {
                        return '<span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 border border-green-200">Active</span>';
                    }
                    return $row->status ? ucfirst($row->status) : '';
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            $pgAdminUsers = User::role('Pg_Admin')->get(['id', 'email', 'name']);

            return view('subscription::index', compact('pgAdminUsers'));
        }
    }

    public function show($id)
    {
        try {
            $subscription = Subscription::byAnyKey($id)->first();
            if (! is_null($subscription)) {
                return response()->json(['status_code' => 200, 'message' => 'View subscription', 'result' => $subscription]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Subscription not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
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

            return response()->json(['status_code' => 200, 'message' => __('subscription::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $subscription = Subscription::byAnyKey($id)->first();
            if (! is_null($subscription)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit subscription ', 'result' => $subscription]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Subscription not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
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

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('subscription::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteSubscriptionRequest $request, $id)
    {
        try {
            $subscription = Subscription::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $subscription->update($data);
            $subscription->delete();

            return response()->json(['status_code' => 200, 'message' => __('subscription::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
