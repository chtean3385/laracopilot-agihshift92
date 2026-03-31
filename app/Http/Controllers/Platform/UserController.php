<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // ── Index (with filters) ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $hotelId  = $request->input('hotel_id');
        $role     = $request->input('role');
        $status   = $request->input('status');

        $query = DB::table('hotel_users')
            ->join('users',  'users.id',  '=', 'hotel_users.user_id')
            ->join('hotels', 'hotels.id', '=', 'hotel_users.hotel_id')
            ->where('users.is_super_admin', '!=', 1)
            ->select(
                'hotel_users.id as pivot_id',
                'hotel_users.hotel_id',
                'hotel_users.user_id',
                'hotel_users.role',
                'hotel_users.is_hotel_admin',
                'hotel_users.status',
                'hotel_users.created_at as joined_at',
                'users.name',
                'users.email',
                'hotels.name as hotel_name',
                'hotels.slug as hotel_slug',
                'hotels.status as hotel_status',
            )
            ->orderBy('users.name')
            ->orderBy('hotels.name');

        if ($hotelId) {
            $query->where('hotel_users.hotel_id', $hotelId);
        }

        if ($role) {
            $query->where('hotel_users.role', $role);
        }

        if ($status) {
            $query->where('hotel_users.status', $status);
        }

        $assignments = $query->paginate(25)->withQueryString();

        // Filter option data
        $hotels = DB::table('hotels')->orderBy('name')->get(['id', 'name']);
        $roles  = DB::table('hotel_users')->distinct()->orderBy('role')->pluck('role');

        return view('platform.users.index', compact('assignments', 'hotels', 'roles'));
    }

    // ── Show (user detail — all hotel assignments) ────────────────────────────

    public function show(int $id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user || $user->is_super_admin) {
            return redirect()->route('platform.users.index')
                ->with('error', 'User not found.');
        }

        $assignments = DB::table('hotel_users')
            ->join('hotels', 'hotels.id', '=', 'hotel_users.hotel_id')
            ->where('hotel_users.user_id', $id)
            ->select(
                'hotel_users.id as pivot_id',
                'hotel_users.hotel_id',
                'hotel_users.role',
                'hotel_users.is_hotel_admin',
                'hotel_users.status',
                'hotel_users.created_at as joined_at',
                'hotels.name as hotel_name',
                'hotels.slug as hotel_slug',
                'hotels.status as hotel_status',
                'hotels.plan as hotel_plan',
            )
            ->orderBy('hotels.name')
            ->get();

        // Guard: only users with at least one hotel assignment are manageable here
        if ($assignments->isEmpty()) {
            return redirect()->route('platform.users.index')
                ->with('error', 'This user has no hotel assignments to manage.');
        }

        return view('platform.users.show', compact('user', 'assignments'));
    }

    // ── Suspend (hotel_users.status → inactive) ───────────────────────────────

    public function suspend(int $id, int $hotelId)
    {
        $pivot = DB::table('hotel_users')
            ->where('user_id', $id)
            ->where('hotel_id', $hotelId)
            ->first();

        if (!$pivot) {
            return redirect()->back()->with('error', 'Assignment not found.');
        }

        DB::table('hotel_users')
            ->where('user_id', $id)
            ->where('hotel_id', $hotelId)
            ->update(['status' => 'inactive', 'updated_at' => now()]);

        $hotel = DB::table('hotels')->where('id', $hotelId)->value('name');
        $user  = DB::table('users')->where('id', $id)->value('name');

        return redirect()->back()
            ->with('success', "{$user}'s access to {$hotel} has been suspended.");
    }

    // ── Activate (hotel_users.status → active) ────────────────────────────────

    public function activate(int $id, int $hotelId)
    {
        $pivot = DB::table('hotel_users')
            ->where('user_id', $id)
            ->where('hotel_id', $hotelId)
            ->first();

        if (!$pivot) {
            return redirect()->back()->with('error', 'Assignment not found.');
        }

        DB::table('hotel_users')
            ->where('user_id', $id)
            ->where('hotel_id', $hotelId)
            ->update(['status' => 'active', 'updated_at' => now()]);

        $hotel = DB::table('hotels')->where('id', $hotelId)->value('name');
        $user  = DB::table('users')->where('id', $id)->value('name');

        return redirect()->back()
            ->with('success', "{$user}'s access to {$hotel} has been reactivated.");
    }
}
