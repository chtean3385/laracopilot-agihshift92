<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelUser;
use App\Models\Role;
use App\Services\HotelContext;
use Illuminate\Http\Request;

class HotelSwitchController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $options = session('crm_hotel_options', []);

        if (empty($options)) {
            $userId = session('crm_user_id');
            $options = HotelUser::where('user_id', $userId)
                ->where('status', 'active')
                ->with('hotel')
                ->get()
                ->filter(fn($hu) => $hu->hotel && $hu->hotel->status === 'active')
                ->map(fn($hu) => [
                    'hotel_id'   => $hu->hotel_id,
                    'hotel_name' => $hu->hotel->name,
                    'role'       => $hu->role,
                ])->values()->toArray();

            session(['crm_hotel_options' => $options]);
        }

        if (count($options) === 1) {
            // Auto-select if only one hotel
            return $this->doSelect($options[0]['hotel_id']);
        }

        return view('admin.hotel-select', compact('options'));
    }

    public function select(Request $request)
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $request->validate(['hotel_id' => 'required|integer']);

        return $this->doSelect((int) $request->hotel_id);
    }

    private function doSelect(int $hotelId)
    {
        $userId = session('crm_user_id');

        // Super Admin bypasses hotel_users check
        if (session('crm_user_role') === 'Super Admin' || $userId === 0) {
            $hotel = \App\Models\Hotel::find($hotelId);
            if (!$hotel) {
                return back()->with('error', 'Hotel not found.');
            }
            app(HotelContext::class)->setHotel($hotelId);
            session([
                'crm_hotel_id'   => $hotelId,
                'crm_hotel_name' => $hotel->name,
            ]);
            return redirect()->route('dashboard')->with('success', 'Now managing: ' . $hotel->name);
        }

        $hotelUser = HotelUser::where('user_id', $userId)
            ->where('hotel_id', $hotelId)
            ->where('status', 'active')
            ->with('hotel')
            ->first();

        if (!$hotelUser || !$hotelUser->hotel || $hotelUser->hotel->status !== 'active') {
            return back()->with('error', 'You do not have access to that hotel.');
        }

        app(HotelContext::class)->setHotel($hotelId);

        $role        = Role::where('name', $hotelUser->role)->first();
        $permissions = $role ? $role->permissionSlugs() : [];

        session([
            'crm_hotel_id'    => $hotelId,
            'crm_hotel_name'  => $hotelUser->hotel->name,
            'crm_user_role'   => $hotelUser->role,
            'crm_permissions' => $permissions,
        ]);

        return redirect()->route('dashboard')->with('success', 'Switched to ' . $hotelUser->hotel->name . '!');
    }
}
