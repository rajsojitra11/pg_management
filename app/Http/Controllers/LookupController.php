<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\City\Models\City;
use Modules\Country\Models\Country;
use Modules\Currency\Models\Currency;
use Modules\State\Models\State;
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
                    $qq->where('name', 'like', '%' . $term . '%')
                        ->orWhere('code', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($c) => ['value' => (string) $c->id, 'label' => $c->name]);

        return response()->json($rows);
    }

    /**
     * States for a country. Filter `country_id` scopes to one country
     * (replaces per-module `state-by-country` JSON action).
     */
    public function states(Request $request): JsonResponse
    {
        $rows = State::select('id', 'name', 'country_id')
            ->when($request->filled('country_id'), fn($q) => $q->where('country_id', $request->input('country_id')))
            ->when($request->filled('q'), fn($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($s) => ['value' => (string) $s->id, 'label' => $s->name]);

        return response()->json($rows);
    }

    /**
     * Cities for a state. Filter `state_id` scopes to one state
     * (replaces per-module `city-by-state` JSON action).
     */
    public function cities(Request $request): JsonResponse
    {
        $rows = City::select('id', 'name', 'state_id')
            ->when($request->filled('state_id'), fn($q) => $q->where('state_id', $request->input('state_id')))
            ->when($request->filled('q'), fn($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($c) => ['value' => (string) $c->id, 'label' => $c->name]);

        return response()->json($rows);
    }

    public function currencies(Request $request): JsonResponse
    {
        $rows = Currency::select('id', 'currency_name', 'currency_symbol')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->input('q');
                $q->where(function ($qq) use ($term) {
                    $qq->where('currency_name', 'like', '%' . $term . '%')
                        ->orWhere('currency_symbol', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('currency_name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($c) => [
                'value' => (string) $c->id,
                'label' => $c->currency_symbol ? ($c->currency_name . ' (' . $c->currency_symbol . ')') : $c->currency_name,
            ]);

        return response()->json($rows);
    }

    public function units(Request $request): JsonResponse
    {
        $rows = Unit::select('id', 'name')
            ->when($request->filled('q'), fn($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->when($request->filled('exclude_id'), fn($q) => $q->where('id', '!=', $request->input('exclude_id')))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($u) => ['value' => (string) $u->id, 'label' => $u->name]);

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
            ->when($request->filled('q'), fn($q) => $q->where(function ($qq) use ($request) {
                $term = $request->input('q');
                $qq->where('name', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%');
            }))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($u) => ['value' => (string) $u->id, 'label' => $u->name . ' (' . $u->email . ')']);

        return response()->json($rows);
    }

    public function years(Request $request): JsonResponse
    {
        $rows = Year::select('id', 'name', 'set_default')
            ->when($request->filled('q'), fn($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->orderByDesc('set_default')
            ->orderByDesc('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($y) => ['value' => (string) $y->id, 'label' => $y->name]);

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
            ->when($request->filled('q'), fn($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->when($request->filled('exclude_id'), fn($q) => $q->where('id', '!=', $request->input('exclude_id')))
            ->orderBy('name')
            ->limit($this->limit($request));

        $rows = $query->get()->map(fn($r) => ['value' => (string) $r->id, 'label' => $r->name]);

        return response()->json($rows);
    }

    public function postPressCategories(Request $request): JsonResponse
    {
        $rows = PostPressCategory::query()
            ->select('id', 'slug', 'name', 'sort')
            ->where('status', 'Active')
            ->when($request->filled('q'), fn($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->orderBy('sort')
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($r) => ['value' => (string) $r->id, 'label' => $r->name, 'slug' => $r->slug]);

        return response()->json($rows);
    }

    /**
     * Post-press master, optionally filtered by category. Pass `category_slug=lamination`
     * (or postpress / process / uv) to narrow to that bucket. Replaces the legacy `parent`
     * ENUM filter — see DB schema §1.10.
     */
    public function postPress(Request $request): JsonResponse
    {
        $rows = PostPress::query()
            ->select('id', 'name', 'post_press_category_id')
            ->where('status', 'Active')
            ->when($request->filled('category_slug'), fn($q) => $q->whereHas(
                'category',
                fn($cq) => $cq->where('slug', $request->input('category_slug'))
            ))
            ->when($request->filled('category_id'), fn($q) => $q->where('post_press_category_id', $request->input('category_id')))
            ->when($request->filled('q'), fn($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->when($request->filled('exclude_id'), fn($q) => $q->where('id', '!=', $request->input('exclude_id')))
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($r) => ['value' => (string) $r->id, 'label' => $r->name]);

        return response()->json($rows);
    }

    /**
     * OrderForms list for the Select2 picker on the four downstream job-card forms
     * (PrintingJobDetail / PlateDetailForm / LaminationOrder / UvOrder).
     * Scoped to the active financial year unless `year_id` is supplied.
     */
    public function orderForms(Request $request): JsonResponse
    {
        $yearId = $request->input('year_id') ?: Year::where('set_default', 1)->value('id');

        // Enforce role-based year access: never leak orders from a forbidden year,
        // even if a disallowed year_id is supplied. Falls the picker back to the
        // current FY (always within the allowed set) when the request is out of range.
        $allowedIds = getUserAllowedYearIds();
        if (is_array($allowedIds) && ! in_array((int) $yearId, $allowedIds, true)) {
            $yearId = Year::where('set_default', 1)->value('id');
        }

        $rows = OrderForm::query()
            ->select('id', 'order_no', 'job_name', 'order_date', 'client_id', 'year_id')
            ->with(['client:id,name'])
            ->when(is_array($allowedIds), fn($q) => $q->whereIn('year_id', $allowedIds))
            ->when($yearId, fn($q) => $q->where('year_id', $yearId))
            // Hide orders that are already fully delivered, i.e. a COMPLETE ("Full")
            // delivery challan exists for them. Used by the Delivery Challan create
            // picker; other forms (printing/plate/lamination/uv) omit this param.
            ->when($request->boolean('exclude_delivered'), function ($q) {
                $q->whereDoesntHave('deliveryChallans', function ($dc) {
                    $dc->whereHas('deliveryType', fn($dt) => $dt->where('name', 'Full'));
                });
            })
            ->when($request->boolean('exclude_pending'), fn($q) => $q->whereNotIn('status', ['Pending']))
            // Hide orders that already have the relevant document (one allowed per order).
            // Each create picker passes only its own flag; lamination & uv are independent
            // processes, so an order with one can still appear in the other's list.
            ->when($request->boolean('exclude_with_printing'), fn($q) => $q->whereNotExists(fn($sub) => $sub
                ->selectRaw('1')->from('printing_job_details')
                ->whereColumn('printing_job_details.order_form_id', 'order_forms.id')
                ->whereNull('printing_job_details.deleted_at')))
            ->when($request->boolean('exclude_with_plate'), fn($q) => $q->whereNotExists(fn($sub) => $sub
                ->selectRaw('1')->from('plate_detail_forms')
                ->whereColumn('plate_detail_forms.order_form_id', 'order_forms.id')
                ->whereNull('plate_detail_forms.deleted_at')))
            ->when($request->boolean('exclude_with_lamination'), fn($q) => $q->whereNotExists(fn($sub) => $sub
                ->selectRaw('1')->from('lamination_orders')
                ->whereColumn('lamination_orders.order_form_id', 'order_forms.id')
                ->whereNull('lamination_orders.deleted_at')))
            ->when($request->boolean('exclude_with_all_lamination'), function ($q) {
                $q->whereRaw('(
                    NOT EXISTS (SELECT 1 FROM order_form_post_press_items ofppi
                        INNER JOIN post_press_categories ppc ON ppc.id = ofppi.post_press_category_id AND ppc.slug = ?
                        WHERE ofppi.order_form_id = order_forms.id)
                    OR
                    EXISTS (SELECT 1 FROM order_form_post_press_items ofppi2
                        INNER JOIN post_press_categories ppc2 ON ppc2.id = ofppi2.post_press_category_id AND ppc2.slug = ?
                        WHERE ofppi2.order_form_id = order_forms.id
                        AND NOT EXISTS (SELECT 1 FROM lamination_order_items loi2
                            INNER JOIN lamination_orders lo2 ON lo2.id = loi2.lamination_order_id
                                AND lo2.deleted_at IS NULL AND lo2.order_form_id = order_forms.id
                            WHERE loi2.post_press_id = ofppi2.post_press_id AND loi2.deleted_at IS NULL))
                )', ['lamination', 'lamination']);
            })
            ->when($request->boolean('exclude_with_uv'), fn($q) => $q->whereNotExists(fn($sub) => $sub
                ->selectRaw('1')->from('uv_orders')
                ->whereColumn('uv_orders.order_form_id', 'order_forms.id')
                ->whereNull('uv_orders.deleted_at')))
            ->when($request->boolean('exclude_with_all_uv'), function ($q) {
                $q->whereRaw('(
                    NOT EXISTS (SELECT 1 FROM order_form_post_press_items ofppi
                        INNER JOIN post_press_categories ppc ON ppc.id = ofppi.post_press_category_id AND ppc.slug = ?
                        WHERE ofppi.order_form_id = order_forms.id)
                    OR
                    EXISTS (SELECT 1 FROM order_form_post_press_items ofppi2
                        INNER JOIN post_press_categories ppc2 ON ppc2.id = ofppi2.post_press_category_id AND ppc2.slug = ?
                        WHERE ofppi2.order_form_id = order_forms.id
                        AND NOT EXISTS (SELECT 1 FROM uv_order_items uvoi2
                            INNER JOIN uv_orders uvo2 ON uvo2.id = uvoi2.uv_order_id
                                AND uvo2.deleted_at IS NULL AND uvo2.order_form_id = order_forms.id
                            WHERE uvoi2.post_press_id = ofppi2.post_press_id AND uvoi2.deleted_at IS NULL))
                )', ['uv', 'uv']);
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->input('q');
                $q->where(fn($qq) => $qq
                    ->where('order_no', 'like', '%' . $term . '%')
                    ->orWhere('job_name', 'like', '%' . $term . '%'));
            })
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit($this->limit($request))
            ->get()
            ->map(fn($o) => [
                'value' => (string) $o->id,
                'label' => $o->order_no . ' — ' . $o->job_name,
                'order_no' => $o->order_no,
                'job_name' => $o->job_name,
                'client_name' => $o->client?->name,
                'order_date' => $o->order_date?->format('Y-m-d'),
            ]);

        return response()->json($rows);
    }

    /**
     * Aggregate sheet totals from the order's printing jobs — used to prefill
     * the first row of a new PrintingJobDetail form.
     */
    public function orderFormPrintingJobPrefill(int $id): JsonResponse
    {
        $totals = OrderFormPrintingJob::query()
            ->where('order_form_id', $id)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(total_sheets), 0) AS total_sheets, COALESCE(SUM(wastage_sheets), 0) AS wastage')
            ->first();

        return response()->json([
            'total_sheets' => (int) ($totals?->total_sheets ?? 0),
            'wastage' => (int) ($totals?->wastage ?? 0),
        ]);
    }

    /**
     * Per-machine plate rows for the PlateDetailForm. Returns one entry per
     * machine attached to the order, prefilled from order_form_printing_jobs.
     */
    public function orderFormPlateDetailPrefill(int $id): JsonResponse
    {
        $totalJobs = (int) OrderFormPrintingJob::query()
            ->where('order_form_id', $id)
            ->sum('no_of_jobs');

        $machines = OrderFormMachine::query()
            ->where('order_form_id', $id)
            ->with('machine:id,name')
            ->orderBy('display_order')
            ->get();

        // When no machines are assigned to the order, create a single generic
        // row with the total job count so the form always has at least one block.
        if ($machines->isEmpty()) {
            return response()->json([[
                'machine_id' => 0,
                'machine_name' => null,
                'no_of_job' => $totalJobs,
                'plates' => 0,
                'extra_plate_client' => 0,
                'extra_plate_vinayak' => 0,
                'screen' => null,
            ]]);
        }

        // No. of Job is prefilled from the order's printing jobs. Multi-machine
        // orders tag each printing job with a machine_id, so sum per machine. A
        // single-machine order leaves jobs untagged, so that lone machine takes
        // the order's whole job count. (plates/extra/screen stay user-entered.)
        // SoftDeletes on OrderFormPrintingJob excludes trashed rows automatically.
        $jobsByMachine = OrderFormPrintingJob::query()
            ->where('order_form_id', $id)
            ->groupBy('machine_id')
            ->selectRaw('machine_id, COALESCE(SUM(no_of_jobs), 0) AS total')
            ->pluck('total', 'machine_id');

        $singleMachine = $machines->count() === 1;

        $rows = $machines->map(fn($ofm) => [
            'machine_id' => (int) $ofm->machine_id,
            'machine_name' => $ofm->machine?->name,
            'no_of_job' => $singleMachine ? $totalJobs : (int) ($jobsByMachine[$ofm->machine_id] ?? 0),
            'plates' => 0,
            'extra_plate_client' => 0,
            'extra_plate_vinayak' => 0,
            'screen' => null,
        ]);

        return response()->json($rows);
    }

    /**
     * Lamination items defined on the parent order's post-press section.
     * Prefills the LaminationOrder line items.
     */
    public function orderFormLaminationPrefill(int $id): JsonResponse
    {
        return $this->orderFormPostPressPrefill($id, 'lamination');
    }

    public function orderFormUvPrefill(int $id): JsonResponse
    {
        return $this->orderFormPostPressPrefill($id, 'uv');
    }

    private function orderFormPostPressPrefill(int $orderFormId, string $slug): JsonResponse
    {
        // Exclude post_press types already present in an existing order for this order form,
        // so the user only sees "pending" types still available to add.
        $existingPpIds = [];
        $orderTable = $slug === 'uv' ? 'uv_orders' : 'lamination_orders';
        $itemTable = $slug === 'uv' ? 'uv_order_items' : 'lamination_order_items';
        $orderFk = $slug === 'uv' ? 'uv_order_id' : 'lamination_order_id';
        $existingOrderId = DB::table($orderTable)
            ->where('order_form_id', $orderFormId)
            ->whereNull('deleted_at')
            ->value('id');
        if ($existingOrderId) {
            $existingPpIds = DB::table($itemTable)
                ->where($orderFk, $existingOrderId)
                ->whereNull('deleted_at')
                ->pluck('post_press_id')
                ->toArray();
        }

        $rows = OrderFormPostPressItem::query()
            ->with(['postPress:id,name', 'category:id,slug'])
            ->where('order_form_id', $orderFormId)
            ->whereHas('category', fn($q) => $q->where('slug', $slug))
            ->when($existingPpIds, fn($q) => $q->whereNotIn('post_press_id', $existingPpIds))
            ->orderBy('row_order')
            ->get()
            ->map(fn($r) => [
                'post_press_id' => (int) $r->post_press_id,
                'name' => $r->postPress?->name,
                'size_1' => null,
                'size_2' => null,
                'quantity' => null,
            ]);

        return response()->json($rows);
    }
}
