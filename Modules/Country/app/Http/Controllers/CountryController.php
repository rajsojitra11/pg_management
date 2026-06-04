<?php

namespace Modules\Country\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Country\Http\Requests\DeleteCountryRequest;
use Modules\Country\Http\Requests\StoreCountryRequest;
use Modules\Country\Http\Requests\UpdateCountryRequest;
use Modules\Country\Models\Country;
use Yajra\DataTables\Facades\DataTables;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:country-list', ['only' => ['index', 'store']]);
        $this->middleware('permission:country-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:country-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:country-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(Country::query())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $flag = country_delete_check($row->id);
                    $show = '';
                    $edit = $flag ? 'country-edit' : '';
                    $delete = $flag ? 'country-delete' : '';
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
            return view('country::index');
        }
    }

    public function create()
    {
        return view('country::create');
    }

    public function store(StoreCountryRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id(); // Add user tracking
            $country = Country::create($data);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'data' => route('country.index'),
                'message' => 'Country added successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }

    public function show($id)
    {
        $country = Country::findByAnyKeyOrFail($id);

        return view('country::show', compact('country'));
    }

    public function edit($id)
    {
        try {
            $country = Country::byAnyKey($id)->first();
            if (! is_null($country)) {
                if (country_delete_check($country->id)) {
                    return response()->json(['status_code' => 200,  'result' => $country]);
                } else {
                    return response()->json(['status_code' => 201, 'message' => 'This country already use in another module.']);
                }
            } else {
                return response()->json(['status_code' => 201,  'message' => 'Country not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateCountryRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $country = Country::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['updated_by'] = auth()->id(); // Add user tracking
            $country->update($data);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'data' => route('country.index'),
                'message' => 'Country updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }

    public function destroy(DeleteCountryRequest $request, $id)
    {
        try {
            $country = Country::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['deleted_by'] = auth()->id(); // Add user tracking

            if (country_delete_check($country->id)) {
                $country->update($data); // Update before delete
                $country->delete();

                return response()->json([
                    'status_code' => 200,
                    'message' => 'Deleted successfully.',
                ]);
            } else {
                return response()->json([
                    'status_code' => 201,
                    'message' => 'This country already use in another module.',
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
