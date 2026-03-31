<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('crm_logged_in') && session('crm_user_role') === 'Super Admin') {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('users')
            ->where('email', $data['email'])
            ->where('is_super_admin', 1)
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials or this account does not have platform access.'])
                ->withInput(['email' => $data['email']]);
        }

        session([
            'crm_logged_in'      => true,
            'crm_user_id'        => $user->id,
            'crm_user_name'      => $user->name,
            'crm_user_email'     => $user->email,
            'crm_user_role'      => 'Super Admin',
            'crm_is_super_admin' => true,
        ]);

        return redirect()->route('platform.dashboard');
    }
}
