<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function isSuperAdmin(): bool
    {
        return session('crm_user_role') === 'Super Admin';
    }

    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $query = User::orderBy('name');
        if (!$this->isSuperAdmin()) {
            $query->where('is_super_admin', false);
        }
        $users = $query->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $roles = Role::orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        if (!$this->isSuperAdmin() && $request->role === 'super_admin') {
            return back()->with('error', 'You are not allowed to assign the Super Admin role.');
        }

        $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:8|confirmed',
            'role'                  => 'required|string',
            'status'                => 'required|in:active,inactive',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => $request->status,
        ]);

        ActivityLogger::log('Created', 'Users', 'Created user: ' . $user->name . ' (' . $user->email . ') with role ' . $user->role);

        return redirect()->route('users.index')->with('success', 'User "' . $user->name . '" created successfully.');
    }

    public function edit($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $user = User::findOrFail($id);
        if (!$this->isSuperAdmin() && $user->is_super_admin) {
            return redirect()->route('users.index')->with('error', 'You are not allowed to edit the Super Admin account.');
        }
        $roles = Role::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $user = User::findOrFail($id);
        if (!$this->isSuperAdmin() && $user->is_super_admin) {
            return redirect()->route('users.index')->with('error', 'You are not allowed to edit the Super Admin account.');
        }
        if (!$this->isSuperAdmin() && $request->role === 'super_admin') {
            return back()->with('error', 'You are not allowed to assign the Super Admin role.');
        }

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role'     => 'required|string',
            'status'   => 'required|in:active,inactive',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->name   = $request->name;
        $user->email  = strtolower(trim($request->email));
        $user->role   = $request->role;
        $user->status = $request->status;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        ActivityLogger::log('Updated', 'Users', 'Updated user: ' . $user->name . ' (' . $user->email . ')');

        return redirect()->route('users.index')->with('success', 'User "' . $user->name . '" updated successfully.');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $user = User::findOrFail($id);

        if ($user->is_super_admin) {
            return back()->with('error', 'The Super Admin account cannot be deleted.');
        }

        if ($user->id === session('crm_user_id')) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        ActivityLogger::log('Deleted', 'Users', 'Deleted user: ' . $name);

        return redirect()->route('users.index')->with('success', 'User "' . $name . '" deleted successfully.');
    }

    public function changePasswordForm()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        return view('admin.users.change-password');
    }

    public function changePassword(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'current_password'      => 'required',
            'password'              => 'required|min:8|confirmed',
        ]);

        $email = session('crm_user_email');

        if ($email === 'superadmin@gmail.com') {
            if ($request->current_password !== 'Super@#3385') {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            return back()->with('error', 'Super Admin password can only be changed in the system configuration.');
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'New password must be different from your current password.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        ActivityLogger::log('Updated', 'Auth', session('crm_user_name') . ' changed their password');

        return back()->with('success', 'Password changed successfully.');
    }
}
