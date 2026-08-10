<?php

namespace Modules\Tenant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Tenant\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\User\Models\UserProfile;
use Spatie\Permission\Models\Role;

class TenantApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:mobile-tenant-list|mobile-tenant-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:mobile-tenant-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:mobile-tenant-view', ['only' => ['show']]);
        $this->middleware('permission:mobile-tenant-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:mobile-tenant-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $user = auth()->user();
        $query = Tenant::with('user', 'pg', 'room');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($roomId = request('room_id')) {
            $query->where('room_id', $roomId);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $tenants = $query->orderByDesc('created_at')->paginate((int) request('per_page', 10));

        $data = $tenants->map(fn ($t) => [
            'id' => (string) $t->id,
            'public_id' => $t->public_id,
            'name' => $t->name,
            'email' => $t->email,
            'phone' => $t->phone,
            'address' => $t->address,
            'status' => $t->status,
            'pg_id' => (string) $t->pg?->id,
            'pg_name' => $t->pg?->pg_name,
            'room_id' => (string) $t->room?->id,
            'room_no' => $t->room?->room_no,
            'bed_no' => $t->bed_no,
            'checkin_date' => $t->checkin_date?->toDateString(),
            'expected_checkout_date' => $t->expected_checkout_date?->toDateString(),
            'monthly_rent' => (float) $t->monthly_rent,
            'security_deposit' => (float) $t->security_deposit,
            'payment_method' => $t->payment_method,
            'id_proof_type' => $t->id_proof_type,
            'id_proof_number' => $t->id_proof_number,
            'id_proof_file' => $t->id_proof_file ? request()->getSchemeAndHttpHost().'/storage/'.$t->id_proof_file : null,
            'emergency_contact_name' => $t->emergency_contact_name,
            'emergency_relation' => $t->emergency_relation,
            'emergency_contact_number' => $t->emergency_contact_number,
            'permanent_state_id' => (string) $t->permanent_state_id,
            'permanent_city_id' => (string) $t->permanent_city_id,
            'permanent_address' => $t->permanent_address,
            'additional_notes' => $t->additional_notes,
            'occupation' => $t->occupation,
            'gender' => $t->gender,
            'date_of_birth' => $t->date_of_birth?->toDateString(),
            'created_at' => $t->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Tenant::with('user', 'pg', 'room', 'permanentState', 'permanentCity')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $t = $query->first();
            if (! is_null($t)) {
                return response()->json([
                    'data' => [
                        'id' => (string) $t->id,
                        'public_id' => $t->public_id,
                        'name' => $t->name,
                        'email' => $t->email,
                        'phone' => $t->phone,
                        'address' => $t->address,
                        'status' => $t->status,
                        'pg_id' => (string) $t->pg?->id,
                        'pg_name' => $t->pg?->pg_name,
                        'room_id' => (string) $t->room?->id,
                        'room_no' => $t->room?->room_no,
                        'bed_no' => $t->bed_no,
                        'checkin_date' => $t->checkin_date?->toDateString(),
                        'expected_checkout_date' => $t->expected_checkout_date?->toDateString(),
                        'monthly_rent' => (float) $t->monthly_rent,
                        'security_deposit' => (float) $t->security_deposit,
                        'payment_method' => $t->payment_method,
                        'id_proof_type' => $t->id_proof_type,
                        'id_proof_number' => $t->id_proof_number,
                        'id_proof_file' => $t->id_proof_file ? request()->getSchemeAndHttpHost().'/storage/'.$t->id_proof_file : null,
                        'emergency_contact_name' => $t->emergency_contact_name,
                        'emergency_relation' => $t->emergency_relation,
                        'emergency_contact_number' => $t->emergency_contact_number,
                        'permanent_state_id' => (string) $t->permanent_state_id,
                        'permanent_state_name' => $t->permanentState?->name,
                        'permanent_city_id' => (string) $t->permanent_city_id,
                        'permanent_city_name' => $t->permanentCity?->name,
                        'permanent_address' => $t->permanent_address,
                        'additional_notes' => $t->additional_notes,
                        'occupation' => $t->occupation,
                        'gender' => $t->gender,
                        'date_of_birth' => $t->date_of_birth?->toDateString(),
                        'created_at' => $t->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Tenant not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreTenantRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $plainPassword = Str::random(12);

            $fullName = ucwords($data['firstname']).' '.ucwords($data['lastname'] ?? '');

            $baseUsername = strtolower(preg_replace('/[^a-z0-9]/', '', $data['firstname']));
            $username = $baseUsername;
            $suffix = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername.$suffix++;
            }

            $user = User::create([
                'name_prefix' => $data['name_prefix'] ?? 'Mr.',
                'name' => $fullName,
                'email' => strtolower($data['email']),
                'mobile' => $data['mobile'],
                'username' => $username,
                'password' => Hash::make($plainPassword),
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);

            $tenantRole = Role::where('name', 'Tenant')->first();
            if ($tenantRole) {
                $user->assignRole($tenantRole);
            }

            $convertDate = function ($val) {
                return (! empty($val)) ? date('Y-m-d', strtotime(str_replace('/', '-', $val))) : null;
            };

            UserProfile::create([
                'user_id' => $user->id,
                'firstname' => ucwords($data['firstname']),
                'lastname' => ucwords($data['lastname'] ?? ''),
                'date_of_birth' => $convertDate($data['date_of_birth']),
                'gender' => $data['gender'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $idProofFile = null;
            if ($request->hasFile('id_proof_file')) {
                $idProofFile = $request->file('id_proof_file')->store('tenant-id-proofs', 'public');
            }

            $tenant = Tenant::create([
                'user_id' => $user->id,
                'name' => $fullName,
                'email' => $data['email'],
                'phone' => $data['mobile'],
                'address' => $data['permanent_address'] ?? '',
                'status' => strtolower($data['status']),
                'pg_id' => $data['pg_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'bed_no' => $data['bed_no'] ?? null,
                'date_of_birth' => $convertDate($data['date_of_birth']),
                'gender' => $data['gender'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'checkin_date' => $convertDate($data['checkin_date']),
                'expected_checkout_date' => $convertDate($data['expected_checkout_date']),
                'monthly_rent' => $data['monthly_rent'] ?? null,
                'security_deposit' => $data['security_deposit'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'id_proof_type' => $data['id_proof_type'] ?? null,
                'id_proof_number' => $data['id_proof_number'] ?? null,
                'id_proof_file' => $idProofFile,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_relation' => $data['emergency_relation'] ?? null,
                'emergency_contact_number' => $data['emergency_contact_number'] ?? null,
                'permanent_state_id' => $data['permanent_state_id'] ?? null,
                'permanent_city_id' => $data['permanent_city_id'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'additional_notes' => $data['additional_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $tenant->load('pg', 'room');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $tenant->id,
                    'public_id' => $tenant->public_id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'phone' => $tenant->phone,
                    'status' => $tenant->status,
                    'pg_id' => (string) $tenant->pg?->id,
                    'pg_name' => $tenant->pg?->pg_name,
                    'room_id' => (string) $tenant->room?->id,
                    'room_no' => $tenant->room?->room_no,
                    'bed_no' => $tenant->bed_no,
                    'created_at' => $tenant->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
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
            $data['updated_by'] = auth()->id();

            if ($request->hasFile('id_proof_file')) {
                if ($tenant->id_proof_file) {
                    Storage::disk('public')->delete($tenant->id_proof_file);
                }
                $data['id_proof_file'] = $request->file('id_proof_file')->store('tenant-id-proofs', 'public');
            }

            $tenant->update($data);
            $tenant->load('pg', 'room');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => (string) $tenant->id,
                    'public_id' => $tenant->public_id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'phone' => $tenant->phone,
                    'status' => $tenant->status,
                    'pg_id' => (string) $tenant->pg?->id,
                    'pg_name' => $tenant->pg?->pg_name,
                    'room_id' => (string) $tenant->room?->id,
                    'room_no' => $tenant->room?->room_no,
                    'bed_no' => $tenant->bed_no,
                    'created_at' => $tenant->created_at?->toIso8601String(),
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
            $user = auth()->user();
            $query = Tenant::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $tenant = $query->firstOrFail();
            $tenant->update([
                'deleted_by' => auth()->id(),
                'room_id' => null,
                'bed_no' => null,
            ]);
            $tenant->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
