<?php

namespace Modules\Room\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Http\Requests\UpdateRoomRequest;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomCategory;
use Modules\Tenant\Models\Tenant;

class RoomApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:mobile-room-list|mobile-room-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:mobile-room-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:mobile-room-view', ['only' => ['show']]);
        $this->middleware('permission:mobile-room-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:mobile-room-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $user = Auth::user();
        $query = Room::with('pg', 'category')
            ->withCount(['tenants as occupied_beds_count' => function ($q) {
                $q->whereIn('status', ['active', 'Active']);
            }]);

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($search = trim((string) request('search'))) {
            $query->where('room_no', 'like', "%{$search}%");
        }

        $rooms = $query->orderBy('room_no')->paginate((int) request('per_page', 10));

        $data = $rooms->map(function ($room) {
            $occupied = $room->occupied_beds_count ?? 0;
            $capacity = (int) ($room->bed_capacity ?? 0);

            return [
                'id' => (string) $room->id,
                'public_id' => $room->public_id,
                'room_no' => $room->room_no,
                'category_id' => (string) $room->category?->id,
                'category_name' => $room->category?->category_name,
                'rent_amount' => (float) $room->rent_amount,
                'bed_capacity' => $capacity,
                'available_beds' => max(0, $capacity - $occupied),
                'status' => $room->status,
                'pg_id' => (string) $room->pg?->id,
                'created_at' => $room->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $query = Room::with('pg', 'category')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $room = $query->first();
            if (! is_null($room)) {
                $occupiedBeds = Tenant::where('room_id', $room->id)
                    ->whereIn('status', ['active', 'Active'])
                    ->pluck('bed_no')
                    ->toArray();

                return response()->json([
                    'data' => [
                        'public_id' => $room->public_id,
                        'room_no' => $room->room_no,
                        'category_id' => (string) $room->category?->id,
                        'category_name' => $room->category?->category_name,
                        'rent_amount' => (float) $room->rent_amount,
                        'bed_capacity' => (int) $room->bed_capacity,
                        'status' => $room->status,
                        'pg_id' => (string) $room->pg?->id,
                        'occupied_beds' => $occupiedBeds,
                        'created_at' => $room->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Room not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreRoomRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::user()->id;
            $room = Room::create($data);
            $room->load('pg', 'category');

            DB::commit();

            return response()->json([
                'data' => [
                    'public_id' => $room->public_id,
                    'room_no' => $room->room_no,
                    'category_id' => (string) $room->category?->id,
                    'category_name' => $room->category?->category_name,
                    'rent_amount' => (float) $room->rent_amount,
                    'bed_capacity' => (int) $room->bed_capacity,
                    'status' => $room->status,
                    'pg_id' => (string) $room->pg?->id,
                    'created_at' => $room->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function update(UpdateRoomRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $query = Room::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $room = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = Auth::user()->id;
            $room->update($data);
            $room->load('pg', 'category');

            DB::commit();

            return response()->json([
                'data' => [
                    'public_id' => $room->public_id,
                    'room_no' => $room->room_no,
                    'category_id' => (string) $room->category?->id,
                    'category_name' => $room->category?->category_name,
                    'rent_amount' => (float) $room->rent_amount,
                    'bed_capacity' => (int) $room->bed_capacity,
                    'status' => $room->status,
                    'pg_id' => (string) $room->pg?->id,
                    'created_at' => $room->created_at?->toIso8601String(),
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
            $user = Auth::user();
            $query = Room::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $room = $query->firstOrFail();
            $data['deleted_by'] = Auth::user()->id;

            $room->update($data);
            $room->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function categoriesByPg(Request $request)
    {
        $pgId = $request->query('pg_id');
        if (! $pgId) {
            return response()->json(['data' => []]);
        }

        $user = Auth::user();
        if ($user->hasRole('Pg_Admin')) {
            $owned = PgManagement::where('owner_id', $user->id)->where('id', $pgId)->exists();
            if (! $owned) {
                return response()->json(['data' => []]);
            }
        }

        $categories = RoomCategory::where('pg_id', $pgId)->where('status', 'active')
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'public_id']);

        return response()->json([
            'data' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'categoryName' => $c->category_name,
                'status' => 'active',
            ]),
        ]);
    }
}
