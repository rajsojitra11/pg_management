<?php

namespace Modules\State\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\State\Http\Requests\DeleteStateRequest;
use Modules\State\Http\Requests\StoreStateRequest;
use Modules\State\Http\Requests\UpdateStateRequest;
use Modules\State\Models\State;
use Yajra\DataTables\Facades\DataTables;

class StateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:state-list', ['only' => ['index', 'store']]);
        $this->middleware('permission:state-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:state-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:state-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->expectsJson()) {
            return response()->json([
                'data' => State::select('id', 'name', 'code')->get(),
            ]);
        }

        if (request()->ajax()) {
            return DataTables::of(State::with('country:id,name,code'))
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $flag = state_delete_check($row->id);
                    $show = '';
                    $edit = true ? 'state-edit' : '';
                    $delete = $flag ? 'state-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            // Country dropdown loads via the canonical `lookup.countries` endpoint.
            // The per-module `state.get.states` cascade endpoint (in routes) is
            // intentionally retained — it's used by City module's cascade JS.
            // See LOOKUP-CONSOLIDATION-001 / R-PROJ-016.
            return view('state::index');
        }
    }

    public function store(StoreStateRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $state = State::create($data);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'data' => route('state.index'),
                'message' => 'State added successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }

    public function show(Request $request)
    {
        try {
            $countryId = $request->id;
            $cacheKey = "states_by_country_{$countryId}";
            $cacheTtl = 86400; // 24 hours in seconds

            $stateData = Cache::remember($cacheKey, $cacheTtl, function () use ($countryId) {
                return State::where('country_id', $countryId)->select('id', 'country_id', 'name', 'code')->get();
            });

            if ($stateData->count() > 0) {
                return response()->json(['status_code' => 200, 'result' => $stateData]);
            } else {
                return response()->json(['status_code' => 403, 'message' => 'State List Not Found']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function getStatesByCountry(Request $request)
    {
        try {
            $countryId = $request->country_id;
            $cacheKey = "states_by_country_{$countryId}";
            $cacheTtl = 86400; // 24 hours in seconds

            $stateData = Cache::remember($cacheKey, $cacheTtl, function () use ($countryId) {
                return State::where('country_id', $countryId)->select('id', 'country_id', 'name', 'code')->get();
            });

            if ($stateData->count() > 0) {
                return response()->json(['status_code' => 200, 'result' => $stateData]);
            } else {
                return response()->json(['status_code' => 403, 'message' => 'State List Not Found']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            // Eager-load the country relation so the edit modal can pre-render
            // the selected option for the async country lookup typeahead.
            $state = State::with('country:id,name,code')->byAnyKey($id)->first();
            if (! is_null($state)) {
                if (state_delete_check($state->id)) {
                    return response()->json(['status_code' => 200,  'result' => $state]);
                } else {
                    return response()->json(['status_code' => 201, 'message' => 'This state already use in another module.']);
                }
            } else {
                return response()->json(['status_code' => 404, 'message' => 'State not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateStateRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $state = State::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $state->update($data);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'data' => route('state.index'),
                'message' => 'State updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }

    public function destroy(DeleteStateRequest $request, $id)
    {
        try {
            $state = State::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            if (state_delete_check($state->id)) {
                $state->update($data); // Update before delete
                $state->delete();

                return response()->json([
                    'status_code' => 200,
                    'message' => 'Deleted successfully.',
                ]);
            } else {
                return response()->json([
                    'status_code' => 201,
                    'message' => 'This state already use in another module.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }
}
