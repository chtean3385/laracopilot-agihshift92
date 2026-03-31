<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelUser;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\HotelContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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

        // ── DB User ──────────────────────────────────────────────────────────
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput($request->only('email'));
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account has been deactivated. Contact the administrator.'])->withInput($request->only('email'));
        }

        // ── Find hotels for this user ────────────────────────────────────────
        $hotelUsers = HotelUser::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('hotel')
            ->get()
            ->filter(fn($hu) => $hu->hotel && $hu->hotel->status === 'active')
            ->values();

        if ($hotelUsers->isEmpty()) {
            return back()->withErrors(['email' => 'Your account is not assigned to any active hotel. Contact the administrator.'])->withInput($request->only('email'));
        }

        // Base session (no hotel yet)
        session([
            'crm_logged_in'   => true,
            'crm_user_id'     => $user->id,
            'crm_user_name'   => $user->name,
            'crm_user_email'  => $email,
            'crm_user_avatar' => $user->avatar,
            'crm_hotel_count' => $hotelUsers->count(),
        ]);

        if ($hotelUsers->count() === 1) {
            return $this->setHotelSession($hotelUsers->first(), $user);
        }

        // Multiple hotels — store options and send to picker
        session(['crm_hotel_options' => $hotelUsers->map(fn($hu) => [
            'hotel_id'   => $hu->hotel_id,
            'hotel_name' => $hu->hotel->name,
            'role'       => $hu->role,
        ])->values()->toArray()]);

        return redirect()->route('select.hotel');
    }

    private function setHotelSession(HotelUser $hotelUser, User $user)
    {
        $hotelId = $hotelUser->hotel_id;

        // Temporarily set hotel context to load scoped role
        app(HotelContext::class)->setHotel($hotelId);

        $role        = Role::where('name', $hotelUser->role)->first();
        $permissions = $role ? $role->permissionSlugs() : [];

        session([
            'crm_hotel_id'    => $hotelId,
            'crm_hotel_name'  => $hotelUser->hotel->name,
            'crm_user_role'   => $hotelUser->role,
            'crm_permissions' => $permissions,
        ]);

        ActivityLogger::log('Logged In', 'Auth', $user->name . ' logged in to ' . $hotelUser->hotel->name . ' from ' . request()->ip());

        return redirect()->route('dashboard')->with('success', 'Welcome back, ' . $user->name . '!');
    }

    public function logout()
    {
        ActivityLogger::log('Logged Out', 'Auth', session('crm_user_name', 'Unknown') . ' logged out');
        $isSuperAdmin = session('crm_is_super_admin', false);
        session()->flush();
        if ($isSuperAdmin) {
            return redirect()->route('platform.login')->with('success', 'Logged out of Platform Admin.');
        }
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
