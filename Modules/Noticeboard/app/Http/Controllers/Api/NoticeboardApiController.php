<?php

namespace Modules\Noticeboard\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Noticeboard\Http\Requests\StoreNoticeboardRequest;
use Modules\Noticeboard\Http\Requests\UpdateNoticeboardRequest;
use Modules\Noticeboard\Models\Noticeboard;

class NoticeboardApiController extends Controller
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
        $user = auth()->user();
        $query = Noticeboard::with('pg', 'user');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $notices = $query->orderByDesc('created_at')->paginate((int) request('per_page', 10));

        $data = $notices->map(fn ($n) => [
            'id' => (string) $n->id,
            'public_id' => $n->public_id,
            'pg_id' => (string) $n->pg?->id,
            'pg_name' => $n->pg?->pg_name,
            'title' => $n->title,
            'image' => $n->image ? Storage::disk('public')->url($n->image) : null,
            'description' => $n->description,
            'status' => $n->status,
            'created_by' => $n->user?->name,
            'created_at' => $n->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $notices->currentPage(),
                'last_page' => $notices->lastPage(),
                'per_page' => $notices->perPage(),
                'total' => $notices->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Noticeboard::with('pg', 'user')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $n = $query->first();
            if (! is_null($n)) {
                return response()->json([
                    'data' => [
                        'id' => (string) $n->id,
                        'public_id' => $n->public_id,
                        'pg_id' => (string) $n->pg?->id,
                        'pg_name' => $n->pg?->pg_name,
                        'title' => $n->title,
                        'image' => $n->image ? Storage::disk('public')->url($n->image) : null,
                        'description' => $n->description,
                        'status' => $n->status,
                        'created_by' => $n->user?->name,
                        'created_at' => $n->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Notice not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreNoticeboardRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            unset($data['notice_type']);
            $data['user_id'] = auth()->id();
            $data['created_by'] = auth()->id();

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('noticeboard', 'public');
            } else {
                $data['image'] = null;
                $data['description'] = $data['description'] ?? '';
            }

            $notice = Noticeboard::create($data);
            $notice->load('pg', 'user');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $notice->id,
                    'public_id' => $notice->public_id,
                    'pg_id' => (string) $notice->pg?->id,
                    'pg_name' => $notice->pg?->pg_name,
                    'title' => $notice->title,
                    'image' => $notice->image ? Storage::disk('public')->url($notice->image) : null,
                    'description' => $notice->description,
                    'status' => $notice->status,
                    'created_by' => $notice->user?->name,
                    'created_at' => $notice->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
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
            $notice = $query->firstOrFail();
            $data = $request->validated();
            unset($data['notice_type']);
            $data['updated_by'] = auth()->id();

            if ($request->hasFile('image')) {
                if ($notice->image) {
                    Storage::disk('public')->delete($notice->image);
                }
                $data['image'] = $request->file('image')->store('noticeboard', 'public');
            }

            if ($request->input('notice_type') === 'text' && $notice->image) {
                Storage::disk('public')->delete($notice->image);
                $data['image'] = null;
            }

            $notice->update($data);
            $notice->load('pg', 'user');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $notice->id,
                    'public_id' => $notice->public_id,
                    'pg_id' => (string) $notice->pg?->id,
                    'pg_name' => $notice->pg?->pg_name,
                    'title' => $notice->title,
                    'image' => $notice->image ? Storage::disk('public')->url($notice->image) : null,
                    'description' => $notice->description,
                    'status' => $notice->status,
                    'created_by' => $notice->user?->name,
                    'created_at' => $notice->created_at?->toIso8601String(),
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
            $query = Noticeboard::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $notice = $query->firstOrFail();

            if ($notice->image) {
                Storage::disk('public')->delete($notice->image);
            }

            $notice->update(['deleted_by' => auth()->id()]);
            $notice->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
