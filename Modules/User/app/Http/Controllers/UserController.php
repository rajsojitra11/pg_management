<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\User\app\Listeners\LogUserAuthentication;
use Modules\User\Http\Requests\DeleteUserRequest;
use Modules\User\Http\Requests\PasswordChangeRequest;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Models\User;
use Modules\User\Models\UserActivityLog;
use Modules\User\Models\UserHierarchy;
use Modules\User\Models\UserProfile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users-list|users-create|users-edit|users-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:users-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:users-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = User::with('profile.parentUser')->where('id', '!=', '1')->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer'));

            if (! auth()->user()->hasRole('Super_Admin')) {
                $subIds = auth()->user()->getAllSubordinateIds();
                if (! empty($subIds)) {
                    $query->whereIn('id', $subIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($search = trim((string) request('filter_search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            }
            if ($role = request('filter_role')) {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            }
            if ($status = request('filter_status')) {
                $query->where('status', $status);
            }

            return DataTables::of($query)

                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    $photo = $row->profile?->profile_photo;
                    if ($photo) {
                        $avatar = '<div class="h-8 w-8 rounded-full bg-gradient-to-br from-[#eef1fb] to-[#dbe3f7] flex items-center justify-center shrink-0 overflow-hidden">'
                            .'<img src="'.e(Storage::url($photo)).'" class="w-full h-full object-cover">'
                            .'</div>';
                    } else {
                        $avatar = '<div class="h-8 w-8 rounded-full bg-gradient-to-br from-[#eef1fb] to-[#dbe3f7] flex items-center justify-center shrink-0 shadow-[inset_0_0_0_1px_#e4e4e7]">'
                            .'<i class="fa-solid fa-user text-[#3D52A0] text-sm"></i>'
                            .'</div>';
                    }

                    return '<div class="flex items-center gap-3">'
                        .$avatar
                        .'<span class="font-medium text-zinc-900">'.e($row->name).'</span>'
                        .'</div>';
                })
                ->addColumn('action', function ($row) {
                    $show = 'users-list';
                    $edit = 'users-edit';
                    $delete = 'users-delete';
                    if ($row->id == 2) {
                        $delete = '';
                    }
                    $assign = ''; // 'assign-user-list';
                    $showURL = route('users.show', $row->public_id ?: $row->id);
                    $editId = $row->public_id ?: $row->id;
                    $editURL = route('users.edit', $editId);

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL', 'assign'));
                })

                ->addColumn('status_display', function ($row) {
                    if ($row->is_blocked) {
                        return '<span class="inline-flex items-center whitespace-nowrap rounded-md border px-2.5 py-0.5 text-xs font-medium" style="background-color:#fef2f2;color:#dc2626;border-color:#fecaca;">Blocked</span>';
                    }
                    if ($row->status === 'Active') {
                        return '<span class="inline-flex items-center whitespace-nowrap rounded-md border px-2.5 py-0.5 text-xs font-medium" style="background-color:#f0fdf4;color:#16a34a;border-color:#bbf7d0;">Active</span>';
                    }

                    return '<span class="inline-flex items-center whitespace-nowrap rounded-md border px-2.5 py-0.5 text-xs font-medium" style="background-color:#fffbeb;color:#d97706;border-color:#fde68a;">Inactive</span>';
                })
                ->addColumn('parent_user', function ($row) {
                    $parent = $row->profile?->parentUser;
                    if ($parent) {
                        return '<span class="text-zinc-700">' . e($parent->email) . '</span>';
                    }
                    return '<span class="text-zinc-400">—</span>';
                })
                ->addColumn('role', function ($row) {
                    $roles = '';
                    foreach ($row->getRoleNames() as $v) {
                        $roles .= '<span class="inline-flex items-center whitespace-nowrap rounded-md border px-2 py-0.5 text-xs font-medium mr-1" style="background-color:var(--erp-bg-muted);color:var(--erp-text-secondary);border-color:var(--erp-border);">'.ucwords(str_replace('_', ' ', $v)).'</span>';
                    }

                    return $roles;
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            $array_role = ['Super_Admin'];
            $roles = Role::whereNotIn('name', $array_role)->pluck('name', 'name')->all();

            return view('user::index', compact('roles'));
        }
    }

    public function create()
    {
        $array_role = ['Super_Admin'];
        $roleMaster = Role::whereNotIn('name', $array_role)->pluck('name', 'name')->all();

        $parentUsers = User::where('status', 'Active')->orderBy('email')->get(['id', 'name', 'email']);

        if (! auth()->user()->hasRole('Super_Admin')) {
            $parentUsers = $parentUsers->where('id', auth()->id());
        }

        return view('user::create', compact('roleMaster', 'parentUsers'));
    }

    public function store(StoreUserRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = new User;
            $user->name_prefix = $request->name_prefix;
            $user->name = ucwords($request->firstname).' '.ucwords($request->lastname);
            $user->email = strtolower($request->email);
            $user->mobile = $request->mobile;
            $user->username = $request->username;
            $user->status = $request->status;
            $user->manager_id = $request->filled('manager_id') ? (int) $request->manager_id : null;
            $user->head_id = $request->filled('head_id') ? (int) $request->head_id : null;
            $user->created_by = auth()->id();
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
            $user->syncRoles([]);
            $user->assignRole($request->input('roles'));

            $userProfile = new UserProfile;
            $userProfile->user_id = $user->id;
            $userProfile->firstname = ucwords($request->firstname);
            $userProfile->lastname = ucwords($request->lastname);
            $userProfile->date_of_birth = (! empty($request->dateofbirth)) ? date('Y-m-d', strtotime($request->dateofbirth)) : null;
            $userProfile->state_id = $request->filled('state_id') ? (int) $request->state_id : null;
            $userProfile->city_id = $request->filled('city_id') ? (int) $request->city_id : null;
            $userProfile->parent_id = $request->filled('parent_id') ? (int) $request->parent_id : null;
            $userProfile->address = $request->address;
            if ($request->hasFile('profile_photo')) {
                $userProfile->profile_photo = $request->file('profile_photo')->store('profile-photos', 'public');
            }
            $result = $userProfile->save();

            if ($result) {
                DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'status_code' => 200,
                        'message' => 'User added successfully',
                        'data' => route('users.index'),
                    ]);
                }

                return redirect()->route('users.index')->with('success', 'User added successfully');
            } else {
                DB::rollback();

                if ($request->ajax()) {
                    return response()->json(['status_code' => 500, 'message' => 'User creation failed'], 500);
                }

                return redirect()->back()->with('warning', 'User added failed');
            }
        } catch (Exception $e) {
            DB::rollback();

            if ($request->ajax()) {
                return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again'], 500);
            }

            return redirect()->back()->with('error', 'Something went wrong. Please try again');
        }
    }

    public function show($id)
    {
        $user = User::findByAnyKey($id);
        if ($user) {
            $user->load('profile.state', 'profile.city');
        }
        if (! $user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        $userRole = $user->roles->pluck('name')->toArray();
        $userProfile = $user->profile;

        if (! $userProfile) {
            $userProfile = new UserProfile;
            $userProfile->user_id = $user->id;
            $userProfile->firstname = explode(' ', $user->name)[0] ?? '';
            $userProfile->lastname = explode(' ', $user->name, 2)[1] ?? '';
        }

        return view('user::show', compact('user', 'userProfile', 'userRole'));
    }

    public function edit($id)
    {
        $user = User::findByAnyKey($id);
        if (! $user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        // First try to find by profile ID
        $userProfile = UserProfile::where('user_id', $user->id)->first();

        // Check if user has a profile
        $userProfile = UserProfile::where('user_id', $user->id)->first();

        // If no profile exists, create a temporary one (not saved)
        if (! $userProfile) {
            $userProfile = new UserProfile;
            $userProfile->id = null;
            $userProfile->user_id = $user->id;
            $userProfile->firstname = explode(' ', $user->name)[0] ?? '';
            $userProfile->lastname = explode(' ', $user->name, 2)[1] ?? '';
            $userProfile->user = $user;
        }

        $userRole = $user->roles->pluck('name')->toArray();
        $array_role = ['Super_Admin'];
        $roleMaster = Role::whereNotIn('name', $array_role)->pluck('name', 'name')->all();

        // Permission grid data (same grouped format as RoleController)
        $permission = $this->getGroupedPermissions();
        $rolePermissionIds = $user->getPermissionsViaRoles()->pluck('id')->toArray();
        $directPermissionIds = $user->getDirectPermissions()->pluck('id')->toArray();

        $parentUsers = User::where('status', 'Active')->where('id', '!=', $user->id)->orderBy('email')->get(['id', 'name', 'email']);

        if (! auth()->user()->hasRole('Super_Admin')) {
            $parentUsers = $parentUsers->where('id', auth()->id());
        }

        return view('user::edit', compact('roleMaster', 'userProfile', 'user', 'userRole', 'permission', 'rolePermissionIds', 'directPermissionIds', 'parentUsers'));
    }

    public function update(UpdateUserRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            // Handle both profile ID and user ID
            $userProfile = null;
            $user = null;

            $user = User::findByAnyKey($id);
            if (! $user) {
                DB::rollback();

                if ($request->ajax()) {
                    return response()->json(['status_code' => 404, 'message' => 'User not found'], 404);
                }

                return redirect()->back()->with('error', 'User not found');
            }
            $userProfile = UserProfile::where('user_id', $user->id)->first();

            // Get original values for comparison
            $originalUserName = $user->name;
            $originalNamePrefix = $user->name_prefix;
            $originalEmail = $user->email;
            $originalMobile = $user->mobile;
            $originalUsername = $user->username;
            $originalParentId = $userProfile->parent_id;
            $originalStatus = $user->status;
            $originalRoles = $user->roles->pluck('name')->toArray();

            // If profile doesn't exist, get or create one
            if (! $userProfile) {
                $userProfile = UserProfile::where('user_id', $user->id)->first();
                if (! $userProfile) {
                    $userProfile = new UserProfile;
                    $userProfile->user_id = $user->id;
                }
            }

            $originalFirstname = $userProfile->firstname;
            $originalLastname = $userProfile->lastname;
            $originalDateOfBirth = $userProfile->date_of_birth;
            $originalStateId = $userProfile->state_id;
            $originalCityId = $userProfile->city_id;

            // Prepare new values
            $newName = ucwords($request->firstname).' '.ucwords($request->lastname);
            $newEmail = strtolower($request->email);
            $newMobile = $request->mobile;
            $newUsername = $request->username;
            $newParentId = $request->filled('parent_id') ? (int) $request->parent_id : null;
            $newStatus = $request->status;
            $newRoles = $request->input('roles');
            $newFirstname = ucwords($request->firstname);
            $newLastname = ucwords($request->lastname);
            $newDateOfBirth = (! empty($request->dateofbirth)) ? date('Y-m-d', strtotime($request->dateofbirth)) : null;

            // Check if any changes were made
            $hasChanges = false;

            // Check user table changes
            if (
                $originalUserName !== $newName ||
                $originalEmail !== $newEmail ||
                $originalMobile !== $newMobile ||
                $originalUsername !== $newUsername ||
                $originalStatus !== $newStatus
            ) {
                $hasChanges = true;
            }

            // Check profile changes
            if (
                $originalFirstname !== $newFirstname ||
                $originalLastname !== $newLastname ||
                $originalDateOfBirth !== $newDateOfBirth ||
                $originalParentId != $newParentId ||
                $originalStateId != $request->state_id ||
                $originalCityId != $request->city_id ||
                $request->hasFile('profile_photo')
            ) {
                $hasChanges = true;
            }

            // Check password change
            if ($request->password) {
                $hasChanges = true;
            }

            // Check roles change
            sort($originalRoles);
            sort($newRoles);
            if ($originalRoles !== $newRoles) {
                $hasChanges = true;
            }

            // Check direct permissions change
            $originalDirectPerms = $user->getDirectPermissions()->pluck('id')->sort()->values()->toArray();
            $newDirectPerms = collect($request->input('direct_permissions', []))->map(fn ($v) => (int) $v)->sort()->values()->toArray();
            if ($originalDirectPerms !== $newDirectPerms) {
                $hasChanges = true;
            }

            // If no changes detected, return info message
            if (! $hasChanges) {
                DB::rollback();

                if ($request->ajax()) {
                    return response()->json([
                        'status_code' => 200,
                        'message' => __('user::message.no_changes_detected') ?: 'No changes detected',
                        'data' => route('users.index'),
                    ]);
                }

                return redirect()->route('users.index')->with('info', __('user::message.no_changes_detected'));
            }

            // Update user information
            $user->name_prefix = $request->name_prefix;
            $user->name = $newName;
            $user->email = $newEmail;
            $user->mobile = $newMobile;
            $user->username = $newUsername;
            $user->status = $newStatus;
            $user->updated_by = auth()->id(); // REQUIRED: Add user tracking
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
            $user->syncRoles([]);
            $user->assignRole($request->input('roles'));

            // Sync direct permissions (additional permissions beyond role)
            // Must cast to int — Spatie treats string numbers as permission names, not IDs
            $directPermIds = array_map('intval', $request->input('direct_permissions', []));
            $user->syncPermissions(Permission::whereIn('id', $directPermIds)->get());

            // Update profile information
            $userProfile->firstname = $newFirstname;
            $userProfile->lastname = $newLastname;
            $userProfile->date_of_birth = $newDateOfBirth;
            $userProfile->parent_id = $newParentId;
            $userProfile->state_id = $request->filled('state_id') ? (int) $request->state_id : null;
            $userProfile->city_id = $request->filled('city_id') ? (int) $request->city_id : null;
            if ($request->hasFile('profile_photo')) {
                $userProfile->profile_photo = $request->file('profile_photo')->store('profile-photos', 'public');
            }
            $result = $userProfile->save();

            if ($result) {
                DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'status_code' => 200,
                        'message' => __('user::message.updated') ?: 'User updated successfully',
                        'data' => route('users.index'),
                    ]);
                }

                return redirect()->route('users.index')->with('success', __('user::message.updated') ?: 'User updated successfully');
            } else {
                DB::rollback();

                if ($request->ajax()) {
                    return response()->json(['status_code' => 500, 'message' => 'User update failed'], 500);
                }

                return redirect()->back()->with('warning', 'User update failed');
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('User update failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString(), 'user_id' => $id]);

            if ($request->ajax()) {
                return response()->json([
                    'status_code' => 500,
                    'message' => app()->environment('local') ? $e->getMessage() : 'Something went wrong. Please try again',
                ], 500);
            }

            return redirect()->back()->with('error', 'Something went wrong. Please try again');
        }
    }

    public function destroy(DeleteUserRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            // Try to find by profile ID first
            $userProfile = UserProfile::find($id);
            $user = null;

            if ($userProfile) {
                // If found by profile ID, get the user
                $user = User::find($userProfile->user_id);
            } else {
                // Otherwise, try to find the user directly
                $user = User::findByAnyKey($id);
                if ($user) {
                    // Check if user has a profile
                    $userProfile = UserProfile::where('user_id', $user->id)->first();
                }
            }

            if (! $user) {
                DB::rollback();

                return response()->json([
                    'status_code' => 404,
                    'message' => 'User not found.',
                ]);
            }

            // Get validated data including user_remark from DeleteUserRequest
            $data = $request->validated();
            $user->deleted_by = auth()->id(); // REQUIRED: Add user tracking
            $user->update($data); // Update before delete to capture user_remark

            // Delete user (this will also soft delete related records due to relationships)
            $user->delete();

            // Delete profile if it exists
            if ($userProfile) {
                $userProfile->delete();
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => __('user::message.deleted') ?: 'Deleted successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function changeLayout(Request $request)
    {
        try {
            $user = User::find(Auth::user()->id);
            $user->menu_style = $request->menu_style;
            if ($request->has('theme')) {
                $user->theme = $request->theme;
            }
            $user->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->back()->with('success', 'Layout changed successfully');
        } catch (Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false], 500);
            }

            return redirect()->back()->with('error', 'Something went wrong. Please try again');
        }
    }



    public function changeTheme(Request $request)
    {
        try {
            $user = User::find(Auth::user()->id);
            $user->theme = $request->theme;
            $user->save();

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function assignUserWise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ], [
            'id.required' => 'Parent user not found.',
        ]);

        if ($validator->fails()) {
            $response = ['status_code' => 201, 'message' => 'Please input proper data.', 'errors' => $validator->errors()];

            return response()->json($response);
        }
        try {
            $id = $request->id;
            $parentUser = User::select('name', 'id')->where('id', '=', $id)->first();
            $getUserChildIds = UserHierarchy::where('parent_id', $id)->pluck('user_id')->toArray();
            $userProfileData = User::select('id', 'name', 'mobile')->where('id', '!=', $id)->whereNotIn('id', $getUserChildIds)->get();
            $array_role = ['Super_Admin', 'Admin'];
            $roleMaster = Role::whereNotIn('name', $array_role)->select('name', 'id')->get();
            $userProfile = [];
            foreach ($roleMaster as $ro) {
                $userArray = [];
                foreach ($userProfileData as $us) {
                    if ($ro->name == $us->getRoleNames()->first()) {
                        $userArray[] = [
                            'id' => $us->id,
                            'name' => $us->name,
                            'user_id' => $us->id,
                            'mobile' => $us->mobile,
                            'role_name' => $us->getRoleNames()->first(),
                        ];
                    }
                }
                $userProfile[$ro->name] = $userArray;
            }

            $getUserTreesData = UserHierarchy::with('user:id,name')->select('id', 'user_id', 'parent_id')->where('parent_id', '=', $id)->get();
            $getUserTree = $this->buildUserTree($getUserTreesData);
            if (! is_null($parentUser) && ! is_null($userProfile)) {
                $return = view('user::assign-user', compact('userProfile', 'parentUser', 'getUserTree'))->render();

                return response()->json(['status_code' => 200, 'message' => 'User found.', 'result' => $return]);
            } else {
                return response()->json(['status_code' => 500, 'message' => 'User not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.'.$e]);
        }
    }

    private function buildUserTree($users)
    {
        $tree = [];
        foreach ($users as $user) {
            $children = UserHierarchy::with('user:id,name')
                ->select('id', 'user_id', 'parent_id')
                ->where('parent_id', '=', $user->user_id)->get();
            $user->children = $this->buildUserTree($children);
            $tree[] = $user;
        }

        return $tree;
    }

    public function assignUserStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id.*' => 'required|integer',
            'parent_id' => 'required|integer',
        ], [
            'child_id.*.required' => 'Select child user.',
            'parent_id.required' => 'Select parent user',
            'child_id.*.integer' => 'Child user required is integer',
            'parent_id.integer' => 'Parent user required is integer',
        ]);

        if ($validator->fails()) {
            $response = ['status_code' => 201, 'message' => 'Please input proper data.', 'errors' => $validator->errors()];

            return response()->json($response);
        }

        DB::beginTransaction();
        try {
            if (isset($request->parent_id) && ($request->child_id)) {
                $childIds = $request->child_id;
                $result = '';
                foreach ($childIds as $childId) {
                    // $userProfile = UserHierarchy::where([]'parent_id', $childId)->first();
                    // if ($userProfile) {
                    //     $userProfile->parent_id = $request->parent_id;
                    //     $userProfile->user_id = $childId;
                    //     $result = $userProfile->save();
                    // }else{
                    $userProfile = new UserHierarchy;
                    $userProfile->parent_id = $request->parent_id;
                    $userProfile->user_id = $childId;
                    $result = $userProfile->save();
                    // }
                }

                if ($result) {
                    DB::commit();

                    return response()->json(['status_code' => 200, 'message' => 'User updated successfully.']);
                } else {
                    return response()->json(['status_code' => 500,  'message' => 'User updated filed.']);
                }
            } else {
                DB::rollback();

                return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
            }
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function assignUserRemove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'parentId' => 'required|integer|exists:users,id',
        ], [
            'id.required' => 'User is required',
            'id.integer' => 'User required is integer',
            'id.exists' => 'Selected user does not exist',
            'parentId.required' => 'Parent user is required',
            'parentId.integer' => 'Parent user is required integer',
            'parentId.exists' => 'Selected user does not exist',
        ]);

        if ($validator->fails()) {
            $response = ['status_code' => 201, 'message' => 'Please input proper data.', 'errors' => $validator->errors()];

            return response()->json($response);
        }

        DB::beginTransaction();
        try {
            $userUpdate = UserHierarchy::where([['user_id', '=', $request->id], ['parent_id', '=', $request->parentId]])->first();
            if ($userUpdate) {
                $result = $userUpdate->delete();
                if ($result) {
                    DB::commit();

                    return response()->json(['status_code' => 200, 'message' => 'User remove successfully.']);
                } else {
                    return response()->json(['status_code' => 500,  'message' => 'User remove filed.']);
                }
            } else {
                return response()->json(['status_code' => 500,  'message' => 'User not found.']);
            }
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function language(Request $request)
    {
        App::setLocale($request->lang);
        session()->put('locale', $request->lang);

        return redirect()->back();
    }

    public function profile()
    {
        $user = Auth::user()->load('profile');

        $activities = UserActivityLog::where(function ($q) use ($user) {
            $q->where('user_id_acting_on', $user->id)
                ->orWhere('user_id', $user->id)
                ->orWhere('created_by', $user->id);
        })
            ->latest('created_at')
            ->limit(50)
            ->get();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        $loginHistory = UserActivityLog::where(function ($q) use ($user) {
            $q->where('user_id_acting_on', $user->id)
                ->orWhere('user_id', $user->id);
        })
            ->whereIn('activity', ['login', 'login_failed', 'logout'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        $lastPasswordChange = UserActivityLog::where(function ($q) use ($user) {
            $q->where('user_id_acting_on', $user->id)
                ->orWhere('user_id', $user->id);
        })
            ->where('activity', 'password_changed')
            ->latest('created_at')
            ->first();

        return view('user::profile', compact('user', 'activities', 'sessions', 'loginHistory', 'lastPasswordChange'));
    }

    public function activities(Request $request)
    {
        $user = Auth::user();
        $query = UserActivityLog::where(function ($q) use ($user) {
            $q->where('user_id_acting_on', $user->id)
                ->orWhere('user_id', $user->id)
                ->orWhere('created_by', $user->id);
        })
            ->latest('created_at');

        if ($request->filled('action')) {
            $query->where('activity', $request->action);
        }
        if ($request->filled('s_date')) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->s_date)));
        }
        if ($request->filled('e_date')) {
            $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->e_date)));
        }

        $perPage = $request->input('per_page', 20);
        $activities = $query->paginate($perPage);

        $html = '';
        foreach ($activities as $log) {
            $html .= view('user::partials.activity-item', compact('log'))->render();
        }

        return response()->json([
            'html' => $html,
            'has_more' => $activities->hasMorePages(),
            'next_page' => $activities->currentPage() + 1,
            'total' => $activities->total(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.Auth::id(),
            'mobile' => 'nullable|regex:/^[0-9]{10,15}$/',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:1000',
        ], [
            'fullname.required' => __('user::message.enter_full_name'),
            'email.required' => __('user::message.enter_valid_email'),
            'email.email' => __('user::message.enter_valid_email'),
            'email.unique' => __('user::message.enter_valid_email'),
            'mobile.regex' => __('user::message.enter_valid_mobile'),
        ]);

        if ($validator->fails()) {
            return response()->json(['status_code' => 422, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $user->name = $request->fullname;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->save();

            $parts = explode(' ', $request->fullname, 2);
            $firstname = $parts[0];
            $lastname = $parts[1] ?? '';

            $profile = UserProfile::firstOrNew(['user_id' => $user->id]);
            $profile->firstname = $firstname;
            $profile->lastname = $lastname;
            $profile->date_of_birth = $request->date_of_birth;
            $profile->address = $request->address;
            $profile->save();

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => __('user::message.success.updated'),
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function avatarUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status_code' => 422, 'message' => $validator->errors()->first('profile_photo')], 422);
        }

        try {
            $user = Auth::user();
            $profile = UserProfile::firstOrNew(['user_id' => $user->id]);
            $profile->profile_photo = $request->file('profile_photo')->store('profile-photos', 'public');
            $profile->save();

            return response()->json([
                'status_code' => 200,
                'message' => __('user::message.success.updated'),
                'photo_url' => Storage::url($profile->profile_photo),
            ]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function changePasswordPage()
    {
        return view('user::change-password');
    }

    public function changePassword(PasswordChangeRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('id', Auth::id())->first();
            if (! is_null($user) && Hash::check($request->current_password, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();

                // Log password change with user remark
                $userRemark = $request->user_remark ?? __('user::message.user_remark_password_changed');
                LogUserAuthentication::logPasswordChange(Auth::id(), $userRemark);

                DB::commit();

                return response()->json([
                    'status_code' => 200,
                    'message' => __('user::message.password').' updated successfully.' ?: 'Your password has been updated.',
                ]);
            } else {
                DB::rollback();

                return response()->json([
                    'status_code' => 403,
                    'message' => __('user::message.current_password').' password does not match.' ?: 'Current password does not match.',
                ]);
            }
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function yearChange(Request $request)
    {
        // Support both old year name and new year ID parameters
        if ($request->has('year_id')) {
            // Check role-based year access
            $allowedIds = getUserAllowedYearIds();
            if (is_array($allowedIds) && ! in_array((int) $request->year_id, $allowedIds)) {
                return response()->json(['status' => 'error', 'message' => 'You do not have access to this year.']);
            }

            // New ID-based approach
            $success = setSessionYear($request->year_id);
            if ($success) {
                return response()->json(['status' => 'success', 'message' => 'Year updated successfully']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid year ID']);
            }
        } elseif ($request->has('year')) {
            // Legacy name-based approach for backward compatibility
            session()->put('year', $request->year);

            return redirect()->back();
        }

        return response()->json(['status' => 'error', 'message' => 'No year parameter provided']);
    }

    public function userBlockUnblock(Request $request)
    {
        // Debug logging - log incoming request data

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'status' => 'required',
            'user_remark' => 'required|string|min:'.config('app.min_comment_length', 3).'|max:'.config('app.max_comment_length', 1000),
        ], [
            'id.required' => __('user::message.id_required'),
            'id.integer' => __('user::message.id_integer'),
            'id.exists' => __('user::message.user_id_not_exist'),
            'status.required' => __('user::message.enter_user_status'),
            'user_remark.required' => __('validation.user_remark_required'),
            'user_remark.min' => __('validation.user_remark_min', ['min' => config('app.min_comment_length', 3)]),
            'user_remark.max' => __('validation.user_remark_max', ['max' => config('app.max_comment_length', 1000)]),
        ]);

        if ($validator->fails()) {
            Log::error('userBlockUnblock Validation Failed:', $validator->errors()->toArray());

            return response()->json(['status_code' => 422, 'message' => 'Validation failed.', 'errors' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {

            $user = User::find($request->id);
            if (! $user) {
                Log::error('userBlockUnblock: User not found with ID: '.$request->id);
                DB::rollback();

                return response()->json(['status_code' => 404, 'message' => 'User not found.']);
            }

            $previousBlocked = $user->is_blocked;

            // Determine activity type before update
            $activity = null;
            if ($request->status == 1 && $previousBlocked != 1) {
                $activity = 'blocked';
            } elseif ($request->status == 0 && $previousBlocked != 0) {
                $activity = 'unblocked';
            }

            // Store action data in session to prevent double logging from HasActivityLogging trait
            if ($activity) {
                session(["user_action_type_{$user->id}" => $activity]);
                session(["user_action_reason_{$user->id}" => $request->user_remark]);
            }

            // Update user blocked status
            $updateResult = $user->update(['is_blocked' => $request->status, 'login_attempts' => 0]);

            if (! $updateResult) {
                Log::error('userBlockUnblock: Failed to update user blocked status');
                DB::rollback();

                return response()->json(['status_code' => 500, 'message' => 'Failed to update user blocked status.']);
            }

            // Determine message
            $message = '';
            if ($request->status == 1 && $previousBlocked != 1) {
                $message = __('user::message.user_blocked_successfully') ?: 'User blocked successfully';
            } elseif ($request->status == 0 && $previousBlocked != 0) {
                $message = __('user::message.user_unblocked_successfully') ?: 'User unblocked successfully';
            } else {
                $message = __('user::message.status_change_successfully') ?: 'Status changed successfully';
            }

            // Note: Activity logging is handled automatically by HasActivityLogging trait via session data

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => $message]);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('userBlockUnblock: Exception caught: '.$e->getMessage());
            Log::error('userBlockUnblock: Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * Handle user activation/deactivation
     */
    public function userActivateDeactivate(Request $request)
    {
        // Debug logging - log incoming request data

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'status' => 'required|in:Active,Inactive',
            'user_remark' => 'required|string|min:'.config('app.min_comment_length', 3).'|max:'.config('app.max_comment_length', 1000),
        ], [
            'id.required' => __('user::message.id_required'),
            'id.integer' => __('user::message.id_integer'),
            'id.exists' => __('user::message.user_id_not_exist'),
            'status.required' => __('user::message.enter_user_status'),
            'status.in' => __('user::message.status_must_be_active_inactive'),
            'user_remark.required' => __('validation.user_remark_required'),
            'user_remark.min' => __('validation.user_remark_min', ['min' => config('app.min_comment_length', 3)]),
            'user_remark.max' => __('validation.user_remark_max', ['max' => config('app.max_comment_length', 1000)]),
        ]);

        if ($validator->fails()) {
            Log::error('UserActivateDeactivate Validation Failed:', $validator->errors()->toArray());

            return response()->json(['status_code' => 422, 'message' => 'Validation failed.', 'errors' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {

            $user = User::find($request->id);
            if (! $user) {
                Log::error('UserActivateDeactivate: User not found with ID: '.$request->id);
                DB::rollback();

                return response()->json(['status_code' => 404, 'message' => 'User not found.']);
            }

            $previousStatus = $user->status;

            // Determine activity type before update
            $activity = null;
            if ($request->status === 'Active' && $previousStatus !== 'Active') {
                $activity = 'activated';
            } elseif ($request->status === 'Inactive' && $previousStatus !== 'Inactive') {
                $activity = 'deactivated';
            }

            // Store action data in session to prevent double logging from HasActivityLogging trait
            if ($activity) {
                session(["user_action_type_{$user->id}" => $activity]);
                session(["user_action_reason_{$user->id}" => $request->user_remark]);
            }

            // Update user status
            $updateResult = $user->update(['status' => $request->status]);

            if (! $updateResult) {
                Log::error('UserActivateDeactivate: Failed to update user status');
                DB::rollback();

                return response()->json(['status_code' => 500, 'message' => 'Failed to update user status.']);
            }

            // Determine message
            $message = '';
            if ($request->status === 'Active' && $previousStatus !== 'Active') {
                $message = __('user::message.user_activated_successfully') ?: 'User activated successfully';
            } elseif ($request->status === 'Inactive' && $previousStatus !== 'Inactive') {
                $message = __('user::message.user_deactivated_successfully') ?: 'User deactivated successfully';
            } else {
                $message = __('user::message.status_change_successfully') ?: 'Status changed successfully';
            }

            // Note: Activity logging is handled automatically by HasActivityLogging trait via session data

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => $message]);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('UserActivateDeactivate: Exception caught: '.$e->getMessage());
            Log::error('UserActivateDeactivate: Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'status_code' => 500,
                'message' => 'Something went wrong. Please try again.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * Get permissions grouped by section → title_tag (same format as RoleController).
     * Filters system-level modules for non-Super_Admin users.
     */
    private function getGroupedPermissions(): array
    {
        $query = Permission::all()->sortBy('name');

        // Exclude System_Administration for all users
        $query = $query->where('title_tag', '!=', 'System_Administration');

        // Hide system-level module permissions from non-Super_Admin users
        if (! auth()->user()->hasRole('Super_Admin')) {
            $restrictedTags = ['Env_Variable', 'Menu_Master', 'Menu_Master_Export', 'Prefix_Master'];
            $query = $query->whereNotIn('title_tag', $restrictedTags);
        }

        $grouped = [];
        foreach ($query as $perm) {
            $section = $perm->section ?? 'Other';
            $tag = Str::slug($perm->title_tag);
            $grouped[$section][$tag]['name'] = $perm->title_tag;
            $grouped[$section][$tag]['child'][] = $perm;
        }

        $sectionOrder = [
            'General', 'Administration', 'Products & Inventory', 'Purchase',
            'Sales', 'Operations', 'Dispatch', 'Testing', 'Reports',
        ];

        $result = [];
        foreach ($sectionOrder as $sec) {
            if (isset($grouped[$sec])) {
                $result[$sec] = $grouped[$sec];
            }
        }
        foreach ($grouped as $sec => $groups) {
            if (! isset($result[$sec])) {
                $result[$sec] = $groups;
            }
        }

        return $result;
    }

    public function logoutEverywhere(Request $request)
    {
        $user = Auth::user();
        $currentSessionId = session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if (request()->expectsJson()) {
            return response()->json(['status_code' => 200, 'message' => __('user::message.logout_everywhere_success')]);
        }

        return redirect()->route('profile')->with('success', __('user::message.logout_everywhere_success'));
    }
}
