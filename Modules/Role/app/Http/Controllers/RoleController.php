<?php

namespace Modules\Role\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Role\Http\Requests\DeleteRoleRequest;
use Modules\Role\Http\Requests\StoreRoleRequest;
use Modules\Role\Http\Requests\UpdateRoleRequest;
use Modules\Role\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function __construct()
    {
        $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request): View
    {
        $roles = Role::with('users', 'yearAccess')->where('id', '!=', '1')->orderBy('name', 'ASC')->get();
        $permission = $this->getGroupedPermissions();

        return view('role::index', compact('roles', 'permission'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(): View
    {
        $permission = $this->getGroupedPermissions(excludeSystemAdmin: ! auth()->user()->hasRole('Super_Admin'));

        return view('role::create', compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreRoleRequest $request)
    {
        $role = Role::create(['name' => $request->input('name'), 'title' => '', 'created_by' => auth()->id()]);
        $permissions = Permission::whereIn('id', $request->input('permission'))->get();
        $role->syncPermissions($permissions);

        // Save year access
        $allYears = $request->boolean('all_years');
        $role->yearAccess()->create([
            'all_years' => $allYears,
            'allowed_year' => $allYears ? null : $request->integer('allowed_year'),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'status_code' => 200,
                'message' => 'Role created successfully',
                'data' => route('roles.index'),
            ]);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id): View
    {
        $role = Role::find($id);
        $rolePermissions = Permission::join('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $role?->id)
            ->get();

        return view('role::show', compact('role', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id): View
    {
        $role = Role::find($id);
        $permission = $this->getGroupedPermissions(excludeSystemAdmin: ! auth()->user()->hasRole('Super_Admin'));
        $rolePermissions = DB::table('role_has_permissions')->where('role_has_permissions.role_id', $role?->id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return view('role::edit', compact('role', 'permission', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateRoleRequest $request, $id)
    {
        $role = Role::find($id);

        if (! $role) {
            if ($request->ajax()) {
                return response()->json(['status_code' => 404, 'message' => 'Role not found'], 404);
            }

            return redirect()->route('roles.index')->with('error', 'Role not found');
        }

        // Check if permissions have changed
        $hasChanges = false;
        $currentPermissions = $role->permissions->pluck('id')->sort()->values()->toArray();
        $newPermissions = collect($request->input('permission'))->map(function ($id) {
            return (int) $id;
        })->sort()->values()->toArray();

        if (count(array_diff($currentPermissions, $newPermissions)) > 0 || count(array_diff($newPermissions, $currentPermissions)) > 0) {
            $hasChanges = true;
        }

        // Check if year access has changed
        $currentYearAccess = $role->yearAccess;
        $newAllYears = $request->boolean('all_years');
        $newAllowedYear = $request->integer('allowed_year');
        if (! $currentYearAccess || $currentYearAccess->all_years !== $newAllYears || $currentYearAccess->allowed_year !== $newAllowedYear) {
            $hasChanges = true;
        }

        // If no changes detected
        if (! $hasChanges) {
            if ($request->ajax()) {
                return response()->json([
                    'status_code' => 200,
                    'message' => __('role::message.no_changes') ?: 'No changes detected',
                    'data' => route('roles.index'),
                ]);
            }

            return redirect()->route('roles.index')
                ->with('info', __('role::message.no_changes'));
        }

        // Perform update
        $role->update(['name' => $request->input('name'), 'updated_by' => auth()->id()]);
        $permissions = Permission::whereIn('id', $request->input('permission'))->get();
        $role->syncPermissions($permissions);

        // Save year access
        $role->yearAccess()->updateOrCreate(
            ['role_id' => $role->id],
            [
                'all_years' => $newAllYears,
                'allowed_year' => $newAllYears ? null : $newAllowedYear,
            ]
        );

        if ($request->ajax()) {
            return response()->json([
                'status_code' => 200,
                'message' => 'Role updated successfully',
                'data' => route('roles.index'),
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    /**
     * Remove the specified resource from storage.
     */
    //     public function destroy(string $id)
    //     {
    //         $role = Role::findOrFail($id);
    //         $data = $request->validated();
    //  $role->deleted_by = auth()->id(); // REQUIRED: Add user tracking
    // $role->update($data); // Update before delete
    // $role->delete();
    //         return response()->json(['status_code' => 200, 'message' => 'Role deleted successfully']);
    //     }

    public function destroy(DeleteRoleRequest $request, string $id)
    {
        $role = Role::find($id);

        // check is not assign to any user
        if ($role->users()->count() > 0) {
            return response()->json([
                'status_code' => 403,
                'message' => 'This role cannot be deleted because it is assigned to users.',
            ]);
        }
        // Optional: Check if role is protected (like 'admin')
        if (in_array($role->name, ['admin'])) {
            return response()->json([
                'status_code' => 403,
                'message' => 'This role cannot be deleted.',
            ]);
        }

        // Detach all permissions before deleting to prevent orphaned role_has_permissions
        $role->syncPermissions([]);

        // Use query builder to update deleted_by without triggering model events
        Role::where('id', $role->id)->update(['deleted_by' => auth()->id()]);

        // Now delete - this will create only one log entry
        $data = $request->validated();
        $role->deleted_by = auth()->id(); // REQUIRED: Add user tracking
        $role->update($data); // Update before delete
        $role->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Group permissions by section, then by title_tag.
     *
     * @return array<string, array<string, array{name: string, child: list<Permission>}>>
     */
    private function getGroupedPermissions(bool $excludeSystemAdmin = false): array
    {
        $query = Permission::all()->sortBy('name');

        if ($excludeSystemAdmin) {
            $query = $query->where('title_tag', '!=', 'System_Administration');
        }

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
            'General',
            'Administration',
            'Products & Inventory',
            'Purchase',
            'Sales',
            'Operations',
            'Dispatch',
            'Testing',
            'Reports',
        ];

        $result = [];
        foreach ($sectionOrder as $sec) {
            if (isset($grouped[$sec])) {
                $result[$sec] = $grouped[$sec];
            }
        }

        // Catch any unmapped sections
        foreach ($grouped as $sec => $groups) {
            if (! isset($result[$sec])) {
                $result[$sec] = $groups;
            }
        }

        return $result;
    }
}
