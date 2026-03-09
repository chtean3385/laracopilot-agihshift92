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
            $date   = Carbon::today()->subDays($i);
            $amount = 0;
            try {
                $amount = Payment::whereDate('created_at', $date)->where('status', 'completed')->sum('amount');
            } catch (\Exception $e) {}
            $weeklyRevenue[] = [
                'day'     => $date->format('D'),
                'date'    => $date->format('d'),
                'label'   => $date->format('D, d M'),
                'amount'  => $amount,
                'isToday' => $date->isToday(),
            ];
        }

        // --- Booking Calendar ---
        $calWeeks  = [];
        $calStart  = $today->copy()->startOfMonth();
        $prevMonth = $calStart->copy()->subMonth();
        $nextMonth = $calStart->copy()->addMonth();

        try {
            $calYear  = (int) request('cal_year',  $today->year);
            $calMonth = (int) request('cal_month', $today->month);

            if ($calMonth < 1)  { $calMonth = 12; $calYear--; }
            if ($calMonth > 12) { $calMonth = 1;  $calYear++; }

            $calStart     = Carbon::create($calYear, $calMonth, 1)->startOfDay();
            $calEnd       = $calStart->copy()->endOfMonth();
            $calGridStart = $calStart->copy()->startOfWeek(Carbon::SUNDAY);
            $calGridEnd   = $calEnd->copy()->endOfWeek(Carbon::SATURDAY);
            $prevMonth    = $calStart->copy()->subMonth();
            $nextMonth    = $calStart->copy()->addMonth();

            $calBookings = Booking::whereNotIn('status', ['cancelled'])
                ->where(function ($q) use ($calGridStart, $calGridEnd) {
                    $q->whereBetween('check_in_date', [$calGridStart->toDateString(), $calGridEnd->toDateString()])
                      ->orWhereBetween('check_out_date', [$calGridStart->toDateString(), $calGridEnd->toDateString()])
                      ->orWhere(function ($q2) use ($calGridStart, $calGridEnd) {
                          $q2->where('check_in_date', '<=', $calGridStart->toDateString())
                             ->where('check_out_date', '>=', $calGridEnd->toDateString());
                      });
                })
                ->get();

            $calDays = [];
            $cur = $calGridStart->copy();
            while ($cur <= $calGridEnd) {
                $ds = $cur->toDateString();
                $calDays[] = [
                    'date'      => $cur->copy(),
                    'ds'        => $ds,
                    'day'       => $cur->day,
                    'inMonth'   => $cur->month === $calMonth,
                    'isToday'   => $cur->isToday(),
                    'checkins'  => $calBookings->filter(fn($b) => $b->check_in_date->toDateString() === $ds)->count(),
                    'checkouts' => $calBookings->filter(fn($b) => $b->check_out_date->toDateString() === $ds)->count(),
                    'staying'   => $calBookings->filter(
                        fn($b) => $b->check_in_date->toDateString() < $ds
                               && $b->check_out_date->toDateString() > $ds
                               && $b->status === 'checked_in'
                    )->count(),
                ];
                $cur->addDay();
            }
            $calWeeks = array_chunk($calDays, 7);
        } catch (\Exception $e) {
            $calWeeks = [];
        }

        return view('admin.dashboard', compact(
            'todayCheckins', 'todayCheckouts', 'availableRooms', 'occupiedRooms',
            'maintenanceRooms', 'totalRooms', 'monthRevenue', 'todayRevenue',
            'pendingPayments', 'totalCustomers', 'newCustomersMonth',
            'recentBookings', 'occupancyRate', 'weeklyRevenue',
            'calWeeks', 'calStart', 'prevMonth', 'nextMonth'
        ));
    }
}
