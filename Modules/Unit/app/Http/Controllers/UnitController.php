<?php

namespace Modules\Unit\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Unit\Http\Requests\DeleteUnitRequest;
use Modules\Unit\Http\Requests\StoreUnitRequest;
use Modules\Unit\Http\Requests\UpdateUnitRequest;
use Modules\Unit\Models\Unit;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:unit-list|unit-create|unit-edit|unit-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:unit-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:unit-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:unit-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(Unit::select('id', 'public_id', 'name', 'unit_value'))
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $flag = unit_delete_check($row->id);
                    $show = '';
                    $edit = true ? 'unit-edit' : '';
                    $delete = $flag ? 'unit-delete' : '';
                    $showURL = '';
                    $editURL = route('unit.edit', $row->public_id ?: $row->id);

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->editColumn('name', function ($row) {
                    return '<a href="javascript:void(0);" class="view" data-id="'.$row->id.'">'.$row->name.'</a>';
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view('unit::index');
    }

    public function create()
    {
        return view('unit::create');
    }

    public function store(StoreUnitRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $unit = Unit::create($data);
            DB::commit();

            return response()->json(['status_code' => 200, 'data' => route('unit.index'), 'message' => 'Unit added successfully.']);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function show(Unit $unit)
    {
        $data['html'] = view('unit::modal', compact('unit'))->render();

        return response()->json($data);
    }

    public function edit($id)
    {
        try {
            $unit = Unit::findByAnyKeyOrFail($id);

            return view('unit::edit', compact('unit'));
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Unit not found.');
        }
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $unit->update($data);
            DB::commit();

            return response()->json(['status_code' => 200, 'data' => route('unit.index'), 'message' => 'Unit updated successfully.']);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteUnitRequest $request, $id)
    {
        try {
            $unit = Unit::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();
            $unit->update($data);

            if (unit_delete_check($unit->id)) {
                $unit->delete();

                return response()->json(['status_code' => 200, 'message' => 'Deleted successfully.']);
            }

            return response()->json(['status_code' => 201, 'message' => 'This unit is in use by another module.']);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
