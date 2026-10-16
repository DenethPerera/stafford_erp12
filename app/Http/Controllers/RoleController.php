<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles (User Types)
     */
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return view('roles.list', compact('roles'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        // Force guard_name to web
        $role = Role::create(['name' => strtolower($validated['name']), 'guard_name' => 'web']);

        activity()
            ->performedOn($role)
            ->event('created')
            ->log('created user type');

        return redirect()->route('roles.index')->with('success', 'User Type created successfully.');
    }

    /**
     * Show the specified role
     */
    public function show(Role $role)
    {
        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the role
     */
    public function edit(Role $role)
    {
        if ($role->name === 'super_admin') {
            return redirect()->route('roles.index')->with('error', 'The Super Admin role cannot be edited.');
        }

        return view('roles.edit', compact('role'));
    }

    /**
     * Update the role
     */
    public function update(Request $request, Role $role)
    {
        if ($role->name === 'super_admin') {
            return redirect()->route('roles.index')->with('error', 'The Super Admin role cannot be edited.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
        ]);

        $oldName = $role->name;
        $role->update(['name' => strtolower($validated['name'])]);

        activity()
            ->performedOn($role)
            ->event('updated')
            ->withProperties(['old' => ['name' => $oldName], 'attributes' => ['name' => $role->name]])
            ->log('updated user type');

        return redirect()->route('roles.index')->with('success', 'User Type updated successfully.');
    }

    /**
     * Remove the role
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'super_admin') {
            return redirect()->route('roles.index')->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', 'Cannot delete this role because users are assigned to it.');
        }

        activity()
            ->performedOn($role)
            ->event('deleted')
            ->log('deleted user type');

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'User Type deleted successfully.');
    }

    /**
     * Display the permissions assignment grid
     */
    public function grid()
    {
        $roles = Role::whereNotIn('name', ['super_admin'])->get();
        
        // Define the available menus based on our naming convention
        $menus = [
            'users' => 'User Management',
            'roles' => 'User Privileges & Roles',
            'profile' => 'My Profile',
            'lab_reports' => 'Lab Reports'
        ];

        // Format existing permissions for the datatable
        $assignedPrivileges = collect();
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            
            foreach ($menus as $menuKey => $menuName) {
                $hasView = in_array("view {$menuKey}", $rolePermissions);
                $hasCreate = in_array("create {$menuKey}", $rolePermissions);
                $hasEdit = in_array("edit {$menuKey}", $rolePermissions);
                $hasDelete = in_array("delete {$menuKey}", $rolePermissions);

                if ($hasView || $hasCreate || $hasEdit || $hasDelete) {
                    $assignedPrivileges->push([
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'menu_key' => $menuKey,
                        'menu_name' => $menuName,
                        'access' => $hasView,
                        'add' => $hasCreate,
                        'edit' => $hasEdit,
                        'delete' => $hasDelete,
                    ]);
                }
            }
        }

        return view('roles.grid', compact('roles', 'menus', 'assignedPrivileges'));
    }

    /**
     * Assign privileges from the form
     */
    public function assignPrivileges(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'menu' => 'required|string',
            'privileges' => 'required|array',
        ]);

        $role = Role::findOrFail($request->role_id);
        $menu = $request->menu;
        $privileges = $request->privileges; // Array of ['view', 'create', 'edit', 'delete']

        // Build the permission names based on checked privileges
        $permissionsToAssign = [];
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            if (isset($privileges[$action])) {
                $permissionsToAssign[] = "{$action} {$menu}";
            }
        }

        // Validate that these permissions actually exist in the DB before assigning
        $validPermissions = Permission::whereIn('name', $permissionsToAssign)->pluck('name')->toArray();

        // First revoke existing permissions for this menu to ensure a clean update
        $existingMenuPermissions = Permission::where('name', 'like', "% {$menu}")->pluck('name')->toArray();
        $role->revokePermissionTo($existingMenuPermissions);

        // Assign the new ones
        if (!empty($validPermissions)) {
            $role->givePermissionTo($validPermissions);
        }

        activity()
            ->performedOn($role)
            ->event('updated')
            ->withProperties(['attributes' => ['menu' => $menu, 'assigned_permissions' => $validPermissions]])
            ->log('assigned privileges');

        return redirect()->route('permissions.grid')->with('success', 'Privileges assigned successfully.');
    }

    /**
     * Revoke all privileges for a menu
     */
    public function revokePrivileges(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'menu' => 'required|string',
        ]);

        $role = Role::findOrFail($request->role_id);
        $menu = $request->menu;

        $existingMenuPermissions = Permission::where('name', 'like', "% {$menu}")->pluck('name')->toArray();
        $role->revokePermissionTo($existingMenuPermissions);

        activity()
            ->performedOn($role)
            ->event('updated')
            ->withProperties(['attributes' => ['menu' => $menu, 'revoked_permissions' => $existingMenuPermissions]])
            ->log('revoked privileges');

        return redirect()->route('permissions.grid')->with('success', 'Privileges revoked successfully.');
    }
}
