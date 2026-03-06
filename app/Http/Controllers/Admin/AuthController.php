<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private array $users = [
        'superadmin@gmail.com'    => ['password' => 'Super@#3385', 'name' => 'Super Admin',    'role' => 'Super Admin',  'avatar' => 'S'],
        'admin@resort.com'        => ['password' => 'admin123',    'name' => 'Admin User',      'role' => 'Admin',        'avatar' => 'A'],
        'manager@resort.com'      => ['password' => 'manager123',  'name' => 'Resort Manager',  'role' => 'Manager',      'avatar' => 'M'],
        'receptionist@resort.com' => ['password' => 'recept123',   'name' => 'Front Desk',      'role' => 'Receptionist', 'avatar' => 'R'],
    ];

    public function showLogin()
    {
        if (session('crm_logged_in')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $email    = $request->email;
        $password = $request->password;

        if (isset($this->users[$email]) && $this->users[$email]['password'] === $password) {
            $user = $this->users[$email];

            if ($user['role'] === 'Super Admin') {
                $permissions = ['*'];
            } else {
                $role = Role::where('name', $user['role'])->with('permissions')->first();
                $permissions = $role ? $role->permissionSlugs() : [];
            }

            session([
                'crm_logged_in'   => true,
                'crm_user_name'   => $user['name'],
                'crm_user_email'  => $email,
                'crm_user_role'   => $user['role'],
                'crm_user_avatar' => $user['avatar'],
                'crm_permissions' => $permissions,
            ]);

            ActivityLogger::log('Logged In', 'Auth', $user['name'] . ' logged in from ' . $request->ip());

            return redirect()->route('dashboard')->with('success', 'Welcome back, ' . $user['name'] . '!');
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->withInput($request->only('email'));
    }

    public function logout()
    {
        ActivityLogger::log('Logged Out', 'Auth', session('crm_user_name', 'Unknown') . ' logged out');
        session()->flush();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
