<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Room;
use App\Models\HotelTimeSlot;
use App\Models\Module;
use App\Services\SlotConflictService;
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
        $fromStr    = $from->toDateString();
        $toStr      = $to->toDateString();

        // Overlap predicate reused for both queries.
        // Per-night: stay overlaps the period if check_in <= period_end AND check_out >= period_start.
        // Per-slot/per-hour: booking_date falls within the period.
        $periodFilter = function ($q) use ($fromStr, $toStr) {
            $q->where(function ($qn) use ($fromStr, $toStr) {
                // Per-night overlap (booking_date is NULL for nightly bookings)
                $qn->whereNull('booking_date')
                   ->where('check_in_date',  '<=', $toStr)
                   ->where('check_out_date', '>=', $fromStr);
            })->orWhere(function ($qs) use ($fromStr, $toStr) {
                // Per-slot / per-hour: booking_date within period
                $qs->whereNotNull('booking_date')
                   ->whereBetween('booking_date', [$fromStr, $toStr]);
            });
        };

        $roomStats      = Room::withCount(['bookings' => $periodFilter])->get();
        $bookingsByType = Booking::with('room')->where($periodFilter)->get()
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

        $bookingsQuery = Booking::with(['customer.documents', 'room', 'bookingGuests'])
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

    public function slotAvailability(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        if (!Module::isEnabled('time-slot-pricing')) return redirect()->route('reports.index')->with('error', 'Time Slot Pricing module is not enabled.');

        $from  = $request->date_from ? Carbon::parse($request->date_from) : Carbon::today();
        $to    = $request->date_to   ? Carbon::parse($request->date_to)   : Carbon::today()->addDays(13);
        $slots = HotelTimeSlot::where('is_active', true)->ordered()->get();
        $rooms = Room::where('pricing_type', 'per_slot')->orderBy('room_number')->get();
        $total = $rooms->count();

        $conflictSvc = new SlotConflictService();

        $availability = [];
        $cur = $from->copy();
        while ($cur <= $to) {
            $ds = $cur->toDateString();
            $dayData = ['date' => $ds, 'label' => $cur->format('D, d M'), 'slots' => []];

            $roomIds = $rooms->pluck('id')->toArray();
            foreach ($slots as $slot) {
                $bookedDetails      = $conflictSvc->getConflictingRoomDetails($slot, $ds);
                $bookedDetails      = array_values(array_filter($bookedDetails, fn($d) => in_array($d['room_id'], $roomIds)));
                $bookedIds          = array_column($bookedDetails, 'room_id');
                $booked             = count($bookedIds);
                $available          = $total - $booked;
                $freeRooms          = $rooms->filter(fn($r) => !in_array($r->id, $bookedIds))->pluck('room_number')->values()->all();

                $dayData['slots'][] = [
                    'slot_id'      => $slot->id,
                    'slot_name'    => $slot->name,
                    'time'         => $slot->start_time . '–' . $slot->end_time,
                    'available'    => $available,
                    'booked'       => $booked,
                    'total'        => $total,
                    'booked_rooms' => $bookedDetails,
                    'free_rooms'   => $freeRooms,
                ];
            }
            $availability[] = $dayData;
            $cur->addDay();
        }

        if ($request->export === 'csv') {
            return $this->exportSlotAvailabilityCsv($availability, $slots, $from, $to);
        }

        return view('admin.reports.slot-availability', compact('availability', 'slots', 'rooms', 'from', 'to'));
    }

    public function slotAvailabilityExport(Request $request)
    {
        return $this->slotAvailability($request->merge(['export' => 'csv']));
    }

    public function slotBookings(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        if (!Module::isEnabled('time-slot-pricing')) {
            return redirect()->route('reports.index')->with('error', 'Time Slot Pricing module is not enabled.');
        }

        $from  = $request->date_from ? Carbon::parse($request->date_from) : Carbon::today()->subDays(6);
        $to    = $request->date_to   ? Carbon::parse($request->date_to)   : Carbon::today();
        $filterSlot = $request->slot_id;

        $query = Booking::with(['customer', 'room', 'timeSlot', 'payments'])
            ->whereNotNull('time_slot_id')
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()]);

        if ($filterSlot) {
            $query->where('time_slot_id', $filterSlot);
        }

        $bookings = $query->orderByDesc('booking_date')->orderByDesc('id')->get();
        $slots    = HotelTimeSlot::where('is_active', true)->ordered()->get();

        $totalBookings  = $bookings->count();
        $totalRevenue   = $bookings->sum('total_amount');
        $avgRevenue     = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

        $slotBreakdown = [];
        foreach ($slots as $slot) {
            $slotBks = $bookings->where('time_slot_id', $slot->id);
            $slotBreakdown[] = [
                'slot'     => $slot,
                'count'    => $slotBks->count(),
                'revenue'  => $slotBks->sum('total_amount'),
                'statuses' => $slotBks->groupBy('status')->map->count(),
            ];
        }

        $statusBreakdown = $bookings->groupBy('status')->map->count();

        if ($request->export === 'csv') {
            return $this->exportSlotBookingsCsv($bookings, $from, $to);
        }

        return view('admin.reports.slot-bookings', compact(
            'bookings', 'slots', 'from', 'to',
            'totalBookings', 'totalRevenue', 'avgRevenue',
            'slotBreakdown', 'statusBreakdown', 'filterSlot'
        ));
    }

    public function slotBookingsExport(Request $request)
    {
        return $this->slotBookings($request->merge(['export' => 'csv']));
    }

    private function exportSlotBookingsCsv($bookings, $from, $to)
    {
        $filename = 'slot-bookings-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];
        $callback = function () use ($bookings) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Booking#', 'Date', 'Room', 'Slot', 'Time', 'Guest', 'Status', 'Amount']);
            foreach ($bookings as $b) {
                fputcsv($out, [
                    $b->booking_number,
                    $b->booking_date?->format('d/m/Y'),
                    $b->room?->room_number ?? '—',
                    $b->timeSlot?->name ?? '—',
                    ($b->timeSlot ? $b->timeSlot->start_time . '–' . $b->timeSlot->end_time : '—'),
                    $b->customer?->name ?? '—',
                    ucfirst($b->status),
                    number_format($b->total_amount, 2),
                ]);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportSlotAvailabilityCsv($availability, $slots, $from, $to)
    {
        $filename = 'slot-availability-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];
        $callback = function () use ($availability, $slots) {
            $out = fopen('php://output', 'w');
            $header = ['Date'];
            foreach ($slots as $s) { $header[] = $s->name . ' Available'; $header[] = $s->name . ' Booked'; }
            fputcsv($out, $header);
            foreach ($availability as $day) {
                $row = [$day['label']];
                foreach ($day['slots'] as $s) { $row[] = $s['available']; $row[] = $s['booked']; }
                fputcsv($out, $row);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
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
            fputcsv($out, ['Booking#', 'Room', 'Check-In', 'Check-Out', 'Guest Name', 'Relation', 'Age', 'Gender', 'Nationality', 'ID Type', 'ID Number', 'Has Signature', 'Has ID Document']);
            foreach ($bookings as $booking) {
                $primary = $booking->customer;
                fputcsv($out, [
                    $booking->booking_number,
                    $booking->room->room_number ?? '',
                    $booking->check_in_date?->format('d/m/Y'),
                    $booking->check_out_date?->format('d/m/Y'),
                    $primary->name ?? '',
                    'Primary Guest',
                    $primary->age ?? '',
                    '',
                    $primary->nationality ?? 'Indian',
                    $primary->id_type ?? '',
                    $primary->id_number ?? '',
                    $primary?->signature ? 'Yes' : 'No',
                    ($primary && $primary->documents->isNotEmpty()) ? 'Yes' : 'No',
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