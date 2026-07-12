<?php

namespace Modules\City\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\City\Http\Requests\DeleteCityRequest;
use Modules\City\Http\Requests\StoreCityRequest;
use Modules\City\Http\Requests\UpdateCityRequest;
use Modules\City\Models\City;
use Modules\State\Models\State;
use Yajra\DataTables\Facades\DataTables;

class CityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:city-list|city-create|city-edit|city-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:city-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:city-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:city-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->expectsJson()) {
            $query = City::select('id', 'name');
            if (request()->filled('state_id')) {
                $query->where('state_id', request('state_id'));
            }

            return response()->json([
                'data' => $query->get(),
            ]);
        }

        if (request()->ajax()) {
            return DataTables::of(City::with('state:id,name,code', 'country:id,name,code'))
                ->addIndexColumn()
                ->addColumn('state_name', function ($row) {
                    return $row->state ? $row->state->name : '';
                })
                ->addColumn('country_name', function ($row) {
                    return $row->country ? $row->country->name : '';
                })
                ->addColumn('action', function ($row) {
                    $flag = city_delete_check($row->id);
                    $show = '';
                    $edit = $flag ? 'city-edit' : '';
                    $delete = $flag ? 'city-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? ($row->created_at)->format('d-m-Y') : '-';
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            // Country dropdown loads via the canonical `lookup.countries` endpoint;
            // State picker is populated by the existing `state.get.states` cascade
            // when a country is selected (additive deprecation per Pattern 7).
            // See LOOKUP-CONSOLIDATION-001 / R-PROJ-016.
            return view('city::index');
        }
    }

    public function store(StoreCityRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $city = City::create($data);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'data' => route('city.index'),
                'message' => 'City added successfully.',
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
            $stateId = $request->id;
            $cacheKey = "cities_by_state_{$stateId}";
            $cacheTtl = 86400; // 24 hours in seconds

            $cityData = Cache::remember($cacheKey, $cacheTtl, function () use ($stateId) {
                return City::where('state_id', $stateId)->select('id', 'name')->get();
            });

            if ($cityData->count() > 0) {
                return response()->json(['status_code' => 200, 'result' => $cityData]);
            } else {
                return response()->json(['status_code' => 403, 'message' => 'City List Not Found']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function getStates($country_id)
    {
        $states = State::where('country_id', $country_id)->get();

        return response()->json($states);
    }

    public function edit($id)
    {
        try {
            // Eager-load country/state so the edit modal can pre-render the
            // selected option for the async country lookup typeahead.
            $city = City::with('country:id,name,code', 'state:id,name,code')->byAnyKey($id)->first();
            if (! is_null($city)) {
                if (city_delete_check($city->id)) {
                    return response()->json(['status_code' => 200, 'message' => 'Edit City', 'result' => $city]);
                } else {
                    return response()->json(['status_code' => 201, 'message' => 'This city already use in another module.']);
                }
            } else {
                return response()->json(['status_code' => 404, 'message' => 'City not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateCityRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $city = City::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $city->update($data);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'data' => route('city.index'),
                'message' => 'City updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }

    public function destroy(DeleteCityRequest $request, $id)
    {
        try {
            $city = City::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            if (city_delete_check($city->id)) {
                $city->update($data); // Update before delete
                $city->delete();

                return response()->json([
                    'status_code' => 200,
                    'message' => 'Deleted successfully.',
                ]);
            } else {
                return response()->json([
                    'status_code' => 201,
                    'message' => 'This city already use in another module.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }

    public function byCountryId($id)
    {
        $cities = City::select('id', 'name')->where('state_id', $id)->get();
        $response = ['status_code' => 200, 'result' => $cities];

        return response()->json($response);
    }
}
