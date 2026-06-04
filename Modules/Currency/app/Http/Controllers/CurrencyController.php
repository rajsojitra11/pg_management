<?php

namespace Modules\Currency\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Currency\Http\Requests\DeleteCurrencyRequest;
use Modules\Currency\Http\Requests\StoreCurrencyRequest;
use Modules\Currency\Http\Requests\UpdateCurrencyRequest;
use Modules\Currency\Models\Currency;
use Yajra\DataTables\DataTables;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:currency-list|currency-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:currency-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:currency-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:currency-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(Currency::select('id', 'public_id', 'currency_name', 'currency_symbol'))
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = '';
                    $edit = true ? 'currency-edit' : '';
                    $delete = $flag ? 'currency-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            return view('currency::index');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCurrencyRequest $request)
    {
        // Validation is handled by StoreCurrencyRequest
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id(); // Add user tracking
            $currency = Currency::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => 'Currency added successfully.']);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $currency = Currency::byAnyKey($id)->first();
            if (! is_null($currency)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit currency ', 'result' => $currency]);

            } else {
                return response()->json(['status_code' => 404, 'message' => 'Currency not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCurrencyRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $currency = Currency::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['updated_by'] = auth()->id(); // Add user tracking
            $currency->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => 'Currency updated successfully.']);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteCurrencyRequest $request, $id)
    {
        try {
            $currency = Currency::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['deleted_by'] = auth()->id(); // Add user tracking

            $currency->update($data); // Update before delete
            $currency->delete();

            return response()->json(['status_code' => 200, 'message' => 'Deleted successfully.']);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
