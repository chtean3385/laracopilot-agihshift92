<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardPreference;
use Illuminate\Http\Request;

class DashboardPreferenceController extends Controller
{
    public function save(Request $request)
    {
        if (!session('crm_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hotelId = (int) session('crm_hotel_id');
        $userId  = (int) session('crm_user_id');

        $data = $request->validate([
            'widget_order'   => 'required|array',
            'hidden_widgets' => 'required|array',
        ]);

        DashboardPreference::updateOrCreate(
            ['hotel_id' => $hotelId, 'user_id' => $userId],
            ['preferences' => $data, 'is_hotel_default' => false]
        );

        return response()->json(['success' => true]);
    }

    public function saveDefault(Request $request)
    {
        if (!session('crm_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hotelId = (int) session('crm_hotel_id');
        $role    = session('crm_user_role');

        if (!in_array($role, ['Super Admin', 'Admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'widget_order'   => 'required|array',
            'hidden_widgets' => 'required|array',
        ]);

        DashboardPreference::updateOrCreate(
            ['hotel_id' => $hotelId, 'user_id' => null],
            ['preferences' => $data, 'is_hotel_default' => true]
        );

        return response()->json(['success' => true]);
    }

    public function reset(Request $request)
    {
        if (!session('crm_logged_in')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hotelId = (int) session('crm_hotel_id');
        $userId  = (int) session('crm_user_id');

        DashboardPreference::where('hotel_id', $hotelId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['success' => true]);
    }
}
