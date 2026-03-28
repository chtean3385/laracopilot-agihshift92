<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Customer;
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

    public function guestRegister(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $from   = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $to     = $request->date_to   ? Carbon::parse($request->date_to)   : Carbon::now()->endOfMonth();
        $search = $request->search;

        $bookingsQuery = Booking::with(['customer', 'room', 'bookingGuests'])
            ->whereBetween('check_in_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', ['confirmed','checked_in','checked_out']);

        if ($search) {
            $bookingsQuery->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%$search%")
                    ->orWhere('id_number', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%"))
                  ->orWhereHas('bookingGuests', fn($g) => $g->where('name', 'like', "%$search%")
                    ->orWhere('id_number', 'like', "%$search%"))
                  ->orWhere('booking_number', 'like', "%$search%");
            });
        }

        $bookings = $bookingsQuery->orderBy('check_in_date')->get();

        if ($request->export === 'csv') {
            return $this->exportGuestRegisterCsv($bookings, $from, $to);
        }

        return view('admin.reports.guest-register', compact('bookings', 'from', 'to', 'search'));
    }

    private function exportGuestRegisterCsv($bookings, $from, $to)
    {
        $filename = 'guest-register-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($bookings) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Booking#', 'Room', 'Check-In', 'Check-Out', 'Guest Name', 'Relation', 'Age', 'Gender', 'Nationality', 'ID Type', 'ID Number', 'DOB', 'Has Signature', 'Has ID Document']);
            foreach ($bookings as $booking) {
                $primary = $booking->customer;
                fputcsv($out, [
                    $booking->booking_number,
                    $booking->room->room_number ?? '',
                    $booking->check_in_date?->format('d/m/Y'),
                    $booking->check_out_date?->format('d/m/Y'),
                    $primary->name ?? '',
                    'Primary Guest',
                    '',
                    '',
                    $primary->nationality ?? 'Indian',
                    $primary->id_type ?? '',
                    $primary->id_number ?? '',
                    $primary->date_of_birth?->format('d/m/Y') ?? '',
                    '',
                    '',
                ]);
                foreach ($booking->bookingGuests as $g) {
                    fputcsv($out, [
                        $booking->booking_number,
                        $booking->room->room_number ?? '',
                        $booking->check_in_date?->format('d/m/Y'),
                        $booking->check_out_date?->format('d/m/Y'),
                        $g->name,
                        $g->relation ?? '',
                        $g->age ?? '',
                        $g->gender ? ucfirst($g->gender) : '',
                        $g->nationality ?? 'Indian',
                        BookingGuest::idTypes()[$g->id_type] ?? $g->id_type ?? '',
                        $g->id_number ?? '',
                        $g->dob?->format('d/m/Y') ?? '',
                        $g->signature ? 'Yes' : 'No',
                        $g->id_document_path ? 'Yes' : 'No',
                    ]);
                }
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}