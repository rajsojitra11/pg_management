<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Models\PgManagement;
use Modules\Tenant\Http\Requests\DeleteTenantRequest;
use Modules\Tenant\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\User\Models\UserProfile;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class TenantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tenant-list|tenant-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:tenant-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:tenant-show', ['only' => ['show']]);
        $this->middleware('permission:tenant-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:tenant-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $user = auth()->user();
            $query = Tenant::with('user', 'pg', 'room')
                ->select('id', 'public_id', 'user_id', 'pg_id', 'room_id', 'email', 'phone', 'checkin_date', 'monthly_rent', 'status');

            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }

            if ($search = trim((string) request('filter_search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('pg_name', function ($row) {
                    return $row->pg?->pg_name ?? '—';
                })
                ->addColumn('room_no', function ($row) {
                    return $row->room?->room_no ?? '—';
                })
                ->addColumn('action', function ($row) {
                    $show = 'tenant-show';
                    $edit = 'tenant-edit';
                    $delete = 'tenant-delete';
                    $showURL = route('tenant.show', $row->public_id ?: $row->id);
                    $editURL = route('tenant.edit', $row->public_id ?: $row->id);
                    $paymentHistoryURL = route('payment.index', ['filter_tenant_id' => $row->id]);

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL', 'paymentHistoryURL'));
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view('tenant::index');
    }

    public function create()
    {
        return view('tenant::create');
    }

    public function store(StoreTenantRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            // Generate a random password
            $plainPassword = Str::random(12);

            $fullName = ucwords($data['firstname']).' '.ucwords($data['lastname'] ?? '');

            // Create User
            $user = User::create([
                'name_prefix' => $data['name_prefix'] ?? 'Mr.',
                'name' => $fullName,
                'email' => strtolower($data['email']),
                'mobile' => $data['mobile'],
                'username' => strtolower($data['firstname']),
                'password' => Hash::make($plainPassword),
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);

            // Assign Tenant role
            $tenantRole = Role::where('name', 'Tenant')->first();
            if ($tenantRole) {
                $user->assignRole($tenantRole);
            }

            // Helper to convert DD-MM-YYYY to Y-m-d
            $convertDate = function ($val) {
                return (! empty($val)) ? date('Y-m-d', strtotime(str_replace('/', '-', $val))) : null;
            };

            // Create UserProfile
            UserProfile::create([
                'user_id' => $user->id,
                'firstname' => ucwords($data['firstname']),
                'lastname' => ucwords($data['lastname'] ?? ''),
                'date_of_birth' => $convertDate($data['date_of_birth'] ?? null),
                'gender' => $data['gender'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Handle ID proof file upload
            $idProofFile = null;
            if ($request->hasFile('id_proof_file')) {
                $idProofFile = $request->file('id_proof_file')->store('tenant-id-proofs', 'public');
            }

            // Create Tenant record
            Tenant::create([
                'user_id' => $user->id,
                'name' => $fullName,
                'email' => $data['email'],
                'phone' => $data['mobile'],
                'address' => $data['permanent_address'] ?? '',
                'status' => strtolower($data['status']),

                // Step 1: PG & Personal
                'pg_id' => $data['pg_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'bed_no' => $data['bed_no'] ?? null,
                'date_of_birth' => $convertDate($data['date_of_birth'] ?? null),
                'gender' => $data['gender'] ?? null,
                'occupation' => $data['occupation'] ?? null,

                // Step 2: Stay & Payment
                'checkin_date' => $convertDate($data['checkin_date'] ?? null),
                'expected_checkout_date' => $convertDate($data['expected_checkout_date'] ?? null),
                'monthly_rent' => $data['monthly_rent'] ?? null,
                'security_deposit' => $data['security_deposit'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'id_proof_type' => $data['id_proof_type'] ?? null,
                'id_proof_number' => $data['id_proof_number'] ?? null,
                'id_proof_file' => $idProofFile,

                // Step 3: Emergency & Permanent Address
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_relation' => $data['emergency_relation'] ?? null,
                'emergency_contact_number' => $data['emergency_contact_number'] ?? null,
                'permanent_state_id' => $data['permanent_state_id'] ?? null,
                'permanent_city_id' => $data['permanent_city_id'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'additional_notes' => $data['additional_notes'] ?? null,

                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('tenant::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function show($id)
    {
        $user = auth()->user();
        $query = Tenant::with('user', 'pg', 'room', 'permanentState', 'permanentCity', 'createdBy', 'updatedBy')
            ->byAnyKey($id);
        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }
        $tenant = $query->firstOrFail();

        return view('tenant::show', compact('tenant'));
    }

    public function payments($id)
    {
        try {
            $user = auth()->user();
            $tenant = Tenant::with('pg', 'room')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $tenant->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $tenant = $tenant->firstOrFail();

            $payments = Payment::with('pg', 'room')
                ->where('tenant_id', $tenant->id)
                ->orderBy('payment_date', 'desc')
                ->get(['id', 'public_id', 'payment_date', 'amount', 'payment_method', 'reference_no', 'verified', 'remarks']);

            return response()->json([
                'status_code' => 200,
                'tenant' => $tenant,
                'payments' => $payments,
            ]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong.']);
        }
    }

    public function edit($id)
    {
        $user = auth()->user();
        $query = Tenant::with('user', 'pg', 'room')->byAnyKey($id);
        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }
        $tenant = $query->firstOrFail();

        $tenant->formatted_date_of_birth = $tenant->date_of_birth ? Carbon::parse($tenant->date_of_birth)->format('d-m-Y') : '';
        $tenant->formatted_checkin_date = $tenant->checkin_date ? Carbon::parse($tenant->checkin_date)->format('d-m-Y') : '';
        $tenant->formatted_expected_checkout_date = $tenant->expected_checkout_date ? Carbon::parse($tenant->expected_checkout_date)->format('d-m-Y') : '';

        $pgList = PgManagement::select('id', 'pg_name')
            ->where('status', 'active')
            ->orderBy('pg_name')
            ->get();

        return view('tenant::edit', compact('tenant', 'pgList'));
    }

    public function update(UpdateTenantRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = Tenant::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $tenant = $query->firstOrFail();
            $data = $request->validated();
            if (empty($data['name']) && ! empty($data['firstname'])) {
                $data['name'] = ucwords($data['firstname']).' '.ucwords($data['lastname'] ?? '');
            }
            unset($data['firstname'], $data['lastname']);
            if (! empty($data['mobile']) && empty($data['phone'])) {
                $data['phone'] = $data['mobile'];
            }
            unset($data['mobile']);
            if (isset($data['status'])) {
                $data['status'] = strtolower($data['status']);
            }
            if ($request->hasFile('id_proof_file')) {
                if ($tenant->id_proof_file) {
                    Storage::disk('public')->delete($tenant->id_proof_file);
                }
                $data['id_proof_file'] = $request->file('id_proof_file')->store('tenant-id-proofs', 'public');
            }
            $data['updated_by'] = auth()->id();
            $tenant->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('tenant::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteTenantRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = Tenant::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $tenant = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $tenant->update($data);
            $tenant->delete();

            return response()->json(['status_code' => 200, 'message' => __('tenant::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
