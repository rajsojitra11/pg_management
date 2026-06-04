<?php

namespace Modules\Year\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Year\Http\Requests\DeleteYearRequest;
use Modules\Year\Http\Requests\StoreYearRequest;
use Modules\Year\Http\Requests\UpdateYearRequest;
use Modules\Year\Models\Year;
use Yajra\DataTables\Facades\DataTables;

class YearController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:year-list', ['only' => ['index']]);
        $this->middleware('permission:year-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:year-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:year-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Year::select('id', 'public_id', 'name', 'full_short', 'short_full', 'short_short', 'full_full', 'short', 'full', 'set_default'))
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $flag = year_delete_check($row->id);
                    $show = '';
                    $edit = ($flag && $row->set_default == 0) ? 'year-edit' : '';
                    $delete = ($flag && $row->set_default == 0) ? 'year-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL', 'flag'));
                })
                ->editColumn('name', function ($row) {
                    return getFormattedYear($row);
                })
                ->editColumn('set_default', function ($row) {
                    return $row->set_default ? 'Yes' : 'No';
                })
                // ->addColumn('other_text', function ($row) {
                //     return  year_delete_check_1($row->id);
                // })
                ->escapeColumns([])
                ->make(true);
        } else {
            return view('year::index');
        }
    }

    public function store(StoreYearRequest $request)
    {
        DB::beginTransaction();
        try {
            $defaultSet = ! empty($request->set_default) ? 1 : 0;
            if ($defaultSet == 1) {
                Year::query()->update(['set_default' => 0]);
            }

            // Generate format fields if not provided
            $formats = generateYearFormats($request->name);

            $year = Year::create([
                'name' => $request->name,
                'full_short' => $request->full_short ?: $formats['full_short'],
                'short_full' => $request->short_full ?: $formats['short_full'],
                'short_short' => $request->short_short ?: $formats['short_short'],
                'full_full' => $request->full_full ?: $formats['full_full'],
                'short' => $request->short ?: $formats['short'],
                'full' => $request->full ?: $formats['full'],
                'set_default' => $defaultSet,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('year::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $year = Year::select('id', 'public_id', 'name', 'full_short', 'short_full', 'short_short', 'full_full', 'short', 'full', 'set_default')->byAnyKey($id)->first();
            if (! is_null($year)) {
                if (year_delete_check($year->id)) {
                    return response()->json(['status_code' => 200, 'message' => 'Edit Year.', 'result' => $year]);
                } else {
                    return response()->json(['status_code' => 201, 'message' => 'This year already use in another module.']);
                }
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Year not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateYearRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $year = Year::byAnyKey($id)->first();
            if (! is_null($year)) {
                $defaultSet = ! empty($request->set_default) ? 1 : 0;
                if ($defaultSet == 1) {
                    Year::query()->update(['set_default' => 0]);
                }

                // Generate format fields if not provided
                $formats = generateYearFormats($request->name);

                $year->update([
                    'name' => $request->name,
                    'full_short' => $request->full_short ?: $formats['full_short'],
                    'short_full' => $request->short_full ?: $formats['short_full'],
                    'short_short' => $request->short_short ?: $formats['short_short'],
                    'full_full' => $request->full_full ?: $formats['full_full'],
                    'short' => $request->short ?: $formats['short'],
                    'full' => $request->full ?: $formats['full'],
                    'set_default' => $defaultSet,
                    'user_remark' => $request->user_remark ?? null,
                    'updated_by' => auth()->id(),
                ]);

                DB::commit();

                return response()->json(['status_code' => 200, 'message' => __('year::message.updated')]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Year not found.']);
            }
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteYearRequest $request, $id)
    {
        try {
            $year = Year::byAnyKey($id)->first();
            if (! is_null($year)) {
                if (year_delete_check($year->id)) {
                    $year->update([
                        'deleted_by' => auth()->id(),
                    ]);
                    $year->delete();

                    return response()->json(['status_code' => 200, 'message' => __('year::message.deleted')]);
                } else {
                    return response()->json(['status_code' => 201, 'message' => 'This year already use in another module.']);
                }
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Year not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    /**
     * Set year in session
     */
    public function setSessionYear(Request $request, $yearId)
    {
        try {
            // Check role-based year access
            $allowedIds = getUserAllowedYearIds();
            if (is_array($allowedIds) && ! in_array((int) $yearId, $allowedIds)) {
                return response()->json([
                    'status_code' => 403,
                    'message' => 'You do not have access to this year.',
                ]);
            }

            $success = setSessionYear($yearId);
            if ($success) {
                // Force session save to ensure persistence
                session()->save();

                // Clear year dropdown cache to reflect new selection
                \Cache::forget('year_dropdown_all');
                \Cache::forget('year_dropdown_fiscal');

                return response()->json([
                    'status_code' => 200,
                    'message' => 'Year set successfully',
                    'year_id' => $yearId,
                    'session_year_id' => session('year_id'),
                    'session_year_name' => session('year'),
                ]);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Year not found',
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }

    /**
     * Search years for AJAX dropdown
     */
    public function searchYears(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $limit = $request->get('limit', 10);

            $years = Year::select('id', 'name', 'full_short', 'short_full', 'short_short', 'full_full', 'short', 'full', 'set_default')
                ->where('name', 'like', '%'.$query.'%')
                ->orderBy('full', 'asc')
                ->limit($limit);

            // Filter by role year access
            $allowedIds = getUserAllowedYearIds();
            if (is_array($allowedIds)) {
                $years->whereIn('id', $allowedIds);
            }

            $years = $years->get();

            $html = '';
            $sessionYearId = getSessionYearId();

            foreach ($years as $year) {
                $class = $sessionYearId == $year->id ? 'active' : '';
                $displayName = getFormattedYear($year);

                $activeStyle = $class === 'active' ? 'background-color:var(--erp-primary);color:var(--erp-primary-fg);' : '';
                $html .= '<a class="block px-3 py-1.5 text-sm rounded-md cursor-pointer transition-colors hover:bg-zinc-100 year-change '.$class.'"
                            style="'.$activeStyle.'"
                            data-value="'.$year->id.'"
                            data-name="'.$year->name.'"
                            title="'.$year->name.'">'.$displayName.'</a>';
            }

            return response()->json([
                'status_code' => 200,
                'html' => $html,
                'count' => $years->count(),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Search failed. Please try again.',
                'html' => '',
            ]);
        }
    }

    /**
     * Get current fiscal year info
     */
    public function getCurrentFiscal()
    {
        try {
            $currentFY = getCurrentFiscalYear();
            $fiscalYears = getFiscalYearRange($currentFY, 3);

            return response()->json([
                'status_code' => 200,
                'current_fiscal_year' => $currentFY,
                'fiscal_year_range' => $fiscalYears,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to get fiscal year info',
            ]);
        }
    }
}
