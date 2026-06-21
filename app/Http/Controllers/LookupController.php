<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\City\Models\City;
use Modules\Complaint\Models\Complaint;
use Modules\Country\Models\Country;
use Modules\Currency\Models\Currency;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\State\Models\State;
use Modules\Tenant\Models\Tenant;
use Modules\Unit\Models\Unit;
use Modules\User\Models\User;
use Modules\Year\Models\Year;

/**
 * Dropdown lookup endpoints for AJAX-driven typeahead controls.
 *
 * Each action takes `?q=term&limit=20` and returns `[{value, label}]` JSON.
 *
 * Filter conventions (consistent across endpoints):
 * - `q`             — substring search on the natural label column
 * - `limit`         — page size (default 20, hard cap 50)
 * - `exclude_id`    — drop a single id (used by self-referential edit pages)
 * - FK filters      — `<snake_case>_id` matching the DB column name
 *                     (`country_id`, `state_id`, …)
 */
class LookupController extends Controller
{
    private const DEFAULT_LIMIT = 20;

    private const MAX_LIMIT = 1000;

    private function limit(Request $request): int
    {
        return min((int) $request->input('limit', self::DEFAULT_LIMIT) ?: self::DEFAULT_LIMIT, self::MAX_LIMIT);
    }

    /**
     * Apply a whitelisted `sort` param (e.g. `-updated_at`, `name`) on top of
     * the endpoint's natural order. Falls back silently if the requested column
     * isn't whitelisted.
     *
     * @param  array<int, string>  $allowed  whitelist of column names (without the optional leading -)
     */
    private function applySort(Builder $q, Request $request, array $allowed): Builder
    {
        $sort = (string) $request->input('sort', '');
        if ($sort === '') {
            return $q;
        }
        $desc = str_starts_with($sort, '-');
        $column = ltrim($sort, '-');
        if (! in_array($column, $allowed, true)) {
            return $q;
        }

        return $desc ? $q->orderByDesc($column) : $q->orderBy($column);
    }

    public function countries(Request $request): JsonResponse
    {
        $rows = Country::select('id', 'name', 'code')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->input('q');
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', '%'.$term.'%')
                        ->orWhere('code', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name]);

        return response()->json($rows);
    }

    /**
     * States for a country. Filter `country_id` scopes to one country
     * (replaces per-module `state-by-country` JSON action).
     */
    public function states(Request $request): JsonResponse
    {
        $rows = State::select('id', 'name', 'country_id')
            ->when($request->filled('country_id'), fn ($q) => $q->where('country_id', $request->input('country_id')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name]);

        return response()->json($rows);
    }

    /**
     * Cities for a state. Filter `state_id` scopes to one state
     * (replaces per-module `city-by-state` JSON action).
     */
    public function cities(Request $request): JsonResponse
    {
        $rows = City::select('id', 'name', 'state_id')
            ->when($request->filled('state_id'), fn ($q) => $q->where('state_id', $request->input('state_id')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name]);

        return response()->json($rows);
    }

    public function currencies(Request $request): JsonResponse
    {
        $rows = Currency::select('id', 'currency_name', 'currency_symbol')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->input('q');
                $q->where(function ($qq) use ($term) {
                    $qq->where('currency_name', 'like', '%'.$term.'%')
                        ->orWhere('currency_symbol', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('currency_name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($c) => [
                'value' => (string) $c->id,
                'label' => $c->currency_symbol ? ($c->currency_name.' ('.$c->currency_symbol.')') : $c->currency_name,
            ]);

        return response()->json($rows);
    }

    public function units(Request $request): JsonResponse
    {
        $rows = Unit::select('id', 'name')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('exclude_id'), fn ($q) => $q->where('id', '!=', $request->input('exclude_id')))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name]);

        return response()->json($rows);
    }

    /**
     * Year master. Sort: `set_default` first, then descending by name (so
     * the most recent fiscal year shows up at the top of the picker).
     */
    public function activeUsers(Request $request): JsonResponse
    {
        $rows = User::select('id', 'name', 'email')
            ->whereNull('deleted_at')
            ->where('status', 'Active')
            ->when($request->filled('q'), fn ($q) => $q->where(function ($qq) use ($request) {
                $term = $request->input('q');
                $qq->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            }))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name.' ('.$u->email.')']);

        return response()->json($rows);
    }

    public function years(Request $request): JsonResponse
    {
        $rows = Year::select('id', 'name', 'set_default')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderByDesc('set_default')
            ->orderByDesc('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($y) => ['value' => (string) $y->id, 'label' => $y->name]);

        return response()->json($rows);
    }

    public function pgList(Request $request): JsonResponse
    {
        $user = auth()->user();

        $rows = PgManagement::select('id', 'pg_name')
            ->where('status', 'active')
            ->when($user->hasRole('Pg_Admin'), fn ($q) => $q->where('owner_id', $user->id))
            ->when($request->filled('q'), fn ($q) => $q->where('pg_name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('pg_name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($p) => ['value' => (string) $p->id, 'label' => $p->pg_name]);

        return response()->json($rows);
    }

    public function roomsByPg(Request $request): JsonResponse
    {
        $pgId = $request->input('pg_id');
        if (! $pgId) {
            return response()->json([]);
        }

        $rows = Room::select('id', 'room_no', 'bed_capacity')
            ->where('pg_id', $pgId)
            ->where('status', 'active')
            ->orderBy('room_no')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->room_no, 'bed_capacity' => $r->bed_capacity]);

        return response()->json($rows);
    }

    public function tenantList(Request $request): JsonResponse
    {
        $user = auth()->user();

        $rows = Tenant::select('id', 'name', 'phone')
            ->where('status', 'active')
            ->when($request->filled('pg_id'), fn ($q) => $q->where('pg_id', $request->input('pg_id')))
            ->when($user->hasRole('Pg_Admin'), fn ($q) => $q->whereHas('pg', fn ($sq) => $sq->where('owner_id', $user->id)))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($qq) use ($request) {
                $term = $request->input('q');
                $qq->where('name', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            }))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($t) => ['value' => (string) $t->id, 'label' => $t->name.' ('.$t->phone.')']);

        return response()->json($rows);
    }

    public function complaintsByPg(Request $request): JsonResponse
    {
        $pgId = $request->input('pg_id');
        if (! $pgId) {
            return response()->json([]);
        }

        $user = auth()->user();

        $rows = Complaint::select('id', 'complaint_no', 'pg_id')
            ->where('pg_id', $pgId)
            ->when($user->hasRole('Pg_Admin'), fn ($q) => $q->whereHas('pg', fn ($sq) => $sq->where('owner_id', $user->id)))
            ->orderByDesc('complaint_no')
            ->limit($this->limit($request))
            ->get()
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->complaint_no]);

        return response()->json($rows);
    }

    /**
     * Generic active-name lookup helper for masters with `id`, `name`, `status` columns.
     */
    private function activeNameLookup(string $modelClass, Request $request): JsonResponse
    {
        /** @var Builder $query */
        $query = $modelClass::query()
            ->select('id', 'name')
            ->where('status', 'Active')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('exclude_id'), fn ($q) => $q->where('id', '!=', $request->input('exclude_id')))
            ->orderBy('name')
            ->limit($this->limit($request));

        $rows = $query->get()->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name]);

        return response()->json($rows);
    }
}
