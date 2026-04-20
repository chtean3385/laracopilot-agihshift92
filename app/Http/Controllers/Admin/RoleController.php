<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $roles = Role::withCount('permissions')->orderBy('id')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $permissions = Permission::orderBy('sort_order')->get()->groupBy('module');
        $role = null;
        return view('admin.roles.edit', compact('permissions', 'role'));
    }

    private const SAAS_ONLY_PERMISSIONS = ['data.truncate'];

    private function filterPermissions(array $slugs): array
    {
        if (session('crm_user_role') === 'Super Admin') {
            return $slugs;
        }
        return array_values(array_diff($slugs, self::SAAS_ONLY_PERMISSIONS));
    }

    public function store(Request $request)
    {
        $hotelId = session('crm_hotel_id');

        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->where('hotel_id', $hotelId),
            ],
            'description' => 'nullable|string|max:255',
        ]);

        $role = Role::create([
            'hotel_id'    => $hotelId,
            'name'        => $request->name,
            'description' => $request->description,
            'is_system'   => false,
        ]);

        $allowed = $this->filterPermissions($request->input('permissions', []));
        $permIds = Permission::whereIn('slug', $allowed)->pluck('id');
        $role->permissions()->sync($permIds);

        ActivityLogger::log('Created', 'Roles', 'Created role: ' . $role->name);

        return redirect()->route('roles.index')->with('success', 'Role "' . $role->name . '" created successfully.');
    }

    public function edit(Role $role)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $permissions    = Permission::orderBy('sort_order')->get()->groupBy('module');
        $rolePermissions = $role->permissions->pluck('slug')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'description' => 'nullable|string|max:255',
        ]);

        if (!$role->is_system) {
            $hotelId = session('crm_hotel_id');
            $request->validate([
                'name' => [
                    'required', 'string', 'max:100',
                    Rule::unique('roles', 'name')->where('hotel_id', $hotelId)->ignore($role->id),
                ],
            ]);
            $role->name = $request->name;
        }

        $role->description = $request->description;
        $role->save();

        $allowed = $this->filterPermissions($request->input('permissions', []));
        $permIds = Permission::whereIn('slug', $allowed)->pluck('id');
        $role->permissions()->sync($permIds);

        ActivityLogger::log('Updated', 'Roles', 'Updated role permissions: ' . $role->name);

        return redirect()->route('roles.index')->with('success', 'Role "' . $role->name . '" updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        ActivityLogger::log('Deleted', 'Roles', 'Deleted role: ' . $role->name);
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
