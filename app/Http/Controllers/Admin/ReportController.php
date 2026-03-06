<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        return view('admin.reports.index');
    }

    public function revenue(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $from     = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $to       = $request->date_to   ? Carbon::parse($request->date_to)   : Carbon::now()->endOfMonth();
        $payments = Payment::with(['booking.customer', 'booking.room'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')->get();
        $totalRevenue = $payments->sum('amount');
        $cashRevenue  = $payments->where('payment_method', 'cash')->sum('amount');
        $cardRevenue  = $payments->where('payment_method', 'card')->sum('amount');
        $upiRevenue   = $payments->where('payment_method', 'upi')->sum('amount');
        $dailyRevenue = $payments->groupBy(fn($p) => Carbon::parse($p->created_at)->format('Y-m-d'))
            ->map(fn($g) => $g->sum('amount'));
        return view('admin.reports.revenue', compact('payments', 'totalRevenue', 'cashRevenue', 'cardRevenue', 'upiRevenue', 'dailyRevenue', 'from', 'to'));
    }

    public function occupancy(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $from      = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $to        = $request->date_to   ? Carbon::parse($request->date_to)   : Carbon::now()->endOfMonth();
        $totalRooms = Room::count();
        $roomStats  = Room::withCount(['bookings' => fn($q) => $q->whereBetween('check_in_date', [$from, $to])])->get();
        $bookingsByType = Booking::with('room')->whereBetween('check_in_date', [$from, $to])->get()
            ->groupBy('room.type')->map(fn($g) => $g->count());
        return view('admin.reports.occupancy', compact('roomStats', 'totalRooms', 'bookingsByType', 'from', 'to'));
    }

    public function bookings(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $from     = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $to       = $request->date_to   ? Carbon::parse($request->date_to)   : Carbon::now()->endOfMonth();
        $bookings = Booking::with(['customer', 'room'])
            ->whereBetween('check_in_date', [$from, $to])
            ->orderBy('check_in_date', 'desc')->get();
        $statusCounts = [
            'confirmed'   => $bookings->where('status', 'confirmed')->count(),
            'checked_in'  => $bookings->where('status', 'checked_in')->count(),
            'checked_out' => $bookings->where('status', 'checked_out')->count(),
            'cancelled'   => $bookings->where('status', 'cancelled')->count(),
        ];
        return view('admin.reports.bookings', compact('bookings', 'statusCounts', 'from', 'to'));
    }
}