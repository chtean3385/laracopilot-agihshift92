<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $today = Carbon::today();

        try {
            $todayCheckins = Booking::with(['customer', 'room'])
                ->whereDate('check_in_date', $today)
                ->where('status', 'confirmed')
                ->get();
        } catch (\Exception $e) {
            $todayCheckins = collect();
        }

        try {
            $todayCheckouts = Booking::with(['customer', 'room'])
                ->whereDate('check_out_date', $today)
                ->where('status', 'checked_in')
                ->get();
        } catch (\Exception $e) {
            $todayCheckouts = collect();
        }

        try {
            $availableRooms   = Room::where('status', 'available')->count();
            $occupiedRooms    = Room::where('status', 'occupied')->count();
            $maintenanceRooms = Room::where('status', 'maintenance')->count();
            $totalRooms       = Room::count();
        } catch (\Exception $e) {
            $availableRooms = $occupiedRooms = $maintenanceRooms = $totalRooms = 0;
        }

        try {
            $monthRevenue = Payment::whereMonth('created_at', $today->month)
                ->whereYear('created_at', $today->year)
                ->where('status', 'completed')
                ->sum('amount');
            $todayRevenue = Payment::whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('amount');
        } catch (\Exception $e) {
            $monthRevenue = $todayRevenue = 0;
        }

        try {
            $pendingPayments = Booking::whereIn('payment_status', ['pending', 'partial'])->count();
        } catch (\Exception $e) {
            $pendingPayments = 0;
        }

        try {
            $totalCustomers    = Customer::count();
            $newCustomersMonth = Customer::whereMonth('created_at', $today->month)
                ->whereYear('created_at', $today->year)
                ->count();
        } catch (\Exception $e) {
            $totalCustomers = $newCustomersMonth = 0;
        }

        try {
            $recentBookings = Booking::with(['customer', 'room'])
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();
        } catch (\Exception $e) {
            $recentBookings = collect();
        }

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $weeklyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $amount = 0;
            try {
                $amount = Payment::whereDate('created_at', $date)->where('status', 'completed')->sum('amount');
            } catch (\Exception $e) {}
            $weeklyRevenue[] = ['day' => $date->format('D'), 'amount' => $amount];
        }

        return view('admin.dashboard', compact(
            'todayCheckins', 'todayCheckouts', 'availableRooms', 'occupiedRooms',
            'maintenanceRooms', 'totalRooms', 'monthRevenue', 'todayRevenue',
            'pendingPayments', 'totalCustomers', 'newCustomersMonth',
            'recentBookings', 'occupancyRate', 'weeklyRevenue'
        ));
    }
}