<?php

namespace Modules\Noticeboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Noticeboard\Http\Requests\DeleteNoticeboardRequest;
use Modules\Noticeboard\Http\Requests\StoreNoticeboardRequest;
use Modules\Noticeboard\Http\Requests\UpdateNoticeboardRequest;
use Modules\Noticeboard\Models\Noticeboard;
use Modules\PgManagement\Models\PgManagement;
use Yajra\DataTables\DataTables;

class NoticeboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:noticeboard-list|noticeboard-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:noticeboard-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:noticeboard-show', ['only' => ['show']]);
        $this->middleware('permission:noticeboard-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:noticeboard-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $user = auth()->user();
            $query = Noticeboard::with('pg', 'user')->select('id', 'public_id', 'user_id', 'pg_id', 'title', 'image', 'status', 'created_at');

            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('pg_name', function ($row) {
                    return $row->pg?->pg_name ?? '—';
                })
                ->addColumn('user_name', function ($row) {
                    return $row->user?->name ?? '—';
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'noticeboard-show';
                    $edit = true ? 'noticeboard-edit' : '';
                    $delete = $flag ? 'noticeboard-delete' : '';
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

            return view('noticeboard::noticeboard.index', compact('pgList'));
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Noticeboard::with('pg', 'user')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $noticeboard = $query->first();
            if (! is_null($noticeboard)) {
                return response()->json(['status_code' => 200, 'message' => 'View notice', 'result' => $noticeboard]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Notice not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreNoticeboardRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $data['created_by'] = auth()->id();

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('noticeboard', 'public');
            }

            Noticeboard::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('noticeboard::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();
            $query = Noticeboard::with('pg', 'user')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $noticeboard = $query->first();
            if (! is_null($noticeboard)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit notice', 'result' => $noticeboard]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Notice not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateNoticeboardRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = Noticeboard::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $noticeboard = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();

            if ($request->hasFile('image')) {
                if ($noticeboard->image) {
                    Storage::disk('public')->delete($noticeboard->image);
                }
                $data['image'] = $request->file('image')->store('noticeboard', 'public');
            }

            $noticeboard->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('noticeboard::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteNoticeboardRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = Noticeboard::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $noticeboard = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            if ($noticeboard->image) {
                Storage::disk('public')->delete($noticeboard->image);
            }

            $noticeboard->update($data);
            $noticeboard->delete();

            return response()->json(['status_code' => 200, 'message' => __('noticeboard::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
