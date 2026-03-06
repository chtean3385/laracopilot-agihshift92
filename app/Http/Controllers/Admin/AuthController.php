<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private const SUPER_ADMIN_EMAIL    = 'superadmin@gmail.com';
    private const SUPER_ADMIN_PASSWORD = 'Super@#3385';
    private const SUPER_ADMIN_NAME     = 'Super Admin';

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

        $email    = strtolower(trim($request->email));
        $password = $request->password;

        if ($email === self::SUPER_ADMIN_EMAIL && $password === self::SUPER_ADMIN_PASSWORD) {
            session([
                'crm_logged_in'   => true,
                'crm_user_id'     => 0,
                'crm_user_name'   => self::SUPER_ADMIN_NAME,
                'crm_user_email'  => self::SUPER_ADMIN_EMAIL,
                'crm_user_role'   => 'Super Admin',
                'crm_user_avatar' => 'S',
                'crm_permissions' => ['*'],
            ]);

            ActivityLogger::log('Logged In', 'Auth', self::SUPER_ADMIN_NAME . ' logged in from ' . $request->ip());
            return redirect()->route('dashboard')->with('success', 'Welcome back, ' . self::SUPER_ADMIN_NAME . '!');
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput($request->only('email'));
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account has been deactivated. Contact the administrator.'])->withInput($request->only('email'));
        }

        $role        = Role::where('name', $user->role)->with('permissions')->first();
        $permissions = $role ? $role->permissionSlugs() : [];

        session([
            'crm_logged_in'   => true,
            'crm_user_id'     => $user->id,
            'crm_user_name'   => $user->name,
            'crm_user_email'  => $email,
            'crm_user_role'   => $user->role,
            'crm_user_avatar' => $user->avatar,
            'crm_permissions' => $permissions,
        ]);

        ActivityLogger::log('Logged In', 'Auth', $user->name . ' logged in from ' . $request->ip());

        return redirect()->route('dashboard')->with('success', 'Welcome back, ' . $user->name . '!');
    }

    public function logout()
    {
        ActivityLogger::log('Logged Out', 'Auth', session('crm_user_name', 'Unknown') . ' logged out');
        session()->flush();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
