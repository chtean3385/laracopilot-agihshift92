<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Customer;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Models\Room;
use App\Models\HotelTimeSlot;
use App\Models\Module;
use App\Services\SlotConflictService;
use App\Support\AnalyticsCache;
use Barryvdh\DomPDF\Facade\Pdf;
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
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('created_at', 'desc')->get();
        $totalRevenue = $payments->sum('amount');
        $cashRevenue  = $payments->where('payment_method', 'cash')->sum('amount');
        $cardRevenue  = $payments->where('payment_method', 'card')->sum('amount');
        $upiRevenue   = $payments->where('payment_method', 'upi')->sum('amount');
        $dailyRevenue = $payments->groupBy(fn($p) => Carbon::parse($p->created_at)->format('Y-m-d'))
            ->map(fn($g) => $g->sum('amount'));

        $headers = ['Date', 'Booking#', 'Guest', 'Room', 'Method', 'Amount'];
        $rows = $payments->map(fn($p) => [
            Carbon::parse($p->created_at)->format('d/m/Y H:i'),
            $p->booking->booking_number ?? '',
            $p->booking->customer->name ?? '',
            $p->booking->room->room_number ?? '',
            ucfirst($p->payment_method ?? ''),
            number_format((float)$p->amount, 2, '.', ''),
        ])->all();

        if ($request->export === 'csv') {
            return $this->streamCsv('revenue-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', $headers, $rows);
        }
        if ($request->export === 'pdf') {
            return $this->renderReportPdf('Revenue Report', $from, $to, [
                'Total Revenue' => '₹' . number_format($totalRevenue, 2),
                'Cash'          => '₹' . number_format($cashRevenue, 2),
                'Card'          => '₹' . number_format($cardRevenue, 2),
                'UPI'           => '₹' . number_format($upiRevenue, 2),
            ], $headers, $rows, [0,0,0,0,0,1], null,
                'revenue-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
        }

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

        $headers = ['Room#', 'Type', 'Bookings'];
        $rows = $roomStats->map(fn($r) => [$r->room_number, $r->type ?? '', $r->bookings_count])->all();

        if ($request->export === 'csv') {
            return $this->streamCsv('occupancy-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', $headers, $rows);
        }
        if ($request->export === 'pdf') {
            return $this->renderReportPdf('Occupancy Report', $from, $to, [
                'Total Rooms' => $totalRooms,
                'Total Bookings' => $roomStats->sum('bookings_count'),
            ], $headers, $rows, [0,0,1], null,
                'occupancy-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
        }

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

        $headers = ['Booking#', 'Guest', 'Phone', 'Room', 'Check-In', 'Check-Out', 'Status', 'Total'];
        $rows = $bookings->map(fn($b) => [
            $b->booking_number,
            $b->customer->name ?? '',
            $b->customer->phone ?? '',
            $b->is_whole_hotel ? 'Whole Hotel' : ($b->room->room_number ?? ''),
            $b->check_in_date?->format('d/m/Y'),
            $b->check_out_date?->format('d/m/Y'),
            $b->status,
            number_format((float)($b->total_amount ?? 0), 2, '.', ''),
        ])->all();

        if ($request->export === 'csv') {
            return $this->streamCsv('bookings-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', $headers, $rows);
        }
        if ($request->export === 'pdf') {
            $kpis = [];
            foreach ($statusCounts as $k => $v) $kpis[ucfirst(str_replace('_',' ',$k))] = $v;
            return $this->renderReportPdf('Bookings Report', $from, $to, $kpis,
                $headers, $rows, [0,0,0,0,0,0,0,1], null,
                'bookings-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
        }

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
            $term = '%' . $search . '%';
            $bookingsQuery->where(function ($q) use ($term) {
                $q->whereHas('customer', fn($c) => $c->where('name', 'ilike', $term)
                    ->orWhere('id_number', 'ilike', $term)
                    ->orWhere('phone', 'ilike', $term))
                  ->orWhereHas('bookingGuests', fn($g) => $g->where('name', 'ilike', $term)
                    ->orWhere('id_number', 'ilike', $term))
                  ->orWhere('booking_number', 'ilike', $term);
            });
        }

        $bookings = $bookingsQuery->orderBy('check_in_date')->get();

        if ($request->export === 'csv') {
            return $this->exportGuestRegisterCsv($bookings, $from, $to);
        }
        if ($request->export === 'excel') {
            return $this->exportGuestRegisterExcel($bookings, $from, $to);
        }
        if ($request->export === 'pdf') {
            $hotel = \App\Models\Hotel::find(session('crm_hotel_id'));
            $pdf = Pdf::loadView('admin.reports.guest-register-pdf',
                compact('bookings', 'from', 'to', 'search', 'hotel'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('guest-register-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
        }

        return view('admin.reports.guest-register', compact('bookings', 'from', 'to', 'search'));
    }

    private function exportGuestRegisterExcel($bookings, $from, $to)
    {
        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="guest-register-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.xls"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($bookings, $from, $to) {
            echo "<html><head><meta charset='utf-8'></head><body>";
            echo "<h2>Guest Register — " . e($from->format('d M Y')) . " to " . e($to->format('d M Y')) . "</h2>";
            echo "<table border='1' cellspacing='0' cellpadding='4'>";
            echo "<tr style='background:#e0f2fe;font-weight:bold;'>"
                . "<th>Booking#</th><th>Room</th><th>Check-In</th><th>Check-Out</th>"
                . "<th>Guest Name</th><th>Relation</th><th>Age</th><th>Gender</th>"
                . "<th>Nationality</th><th>ID Type</th><th>ID Number</th>"
                . "<th>Has Signature</th><th>Has ID Document</th></tr>";
            foreach ($bookings as $booking) {
                $primary = $booking->customer;
                $roomLabel = $booking->is_whole_hotel ? 'Whole Hotel' : ($booking->room?->room_number ?? '');
                echo "<tr>"
                    . "<td>" . e($booking->booking_number) . "</td>"
                    . "<td>" . e($roomLabel) . "</td>"
                    . "<td>" . e($booking->check_in_date?->format('d/m/Y')) . "</td>"
                    . "<td>" . e($booking->check_out_date?->format('d/m/Y')) . "</td>"
                    . "<td>" . e($primary->name ?? '') . "</td>"
                    . "<td>Primary Guest</td>"
                    . "<td>" . e($primary->age ?? '') . "</td>"
                    . "<td></td>"
                    . "<td>" . e($primary->nationality ?? 'Indian') . "</td>"
                    . "<td>" . e($primary->id_type ?? '') . "</td>"
                    . "<td>" . e($primary->id_number ?? '') . "</td>"
                    . "<td>" . ($primary?->signature ? 'Yes' : 'No') . "</td>"
                    . "<td>" . (($primary && $primary->documents->isNotEmpty()) ? 'Yes' : 'No') . "</td>"
                    . "</tr>";
                foreach ($booking->bookingGuests as $g) {
                    echo "<tr>"
                        . "<td>" . e($booking->booking_number) . "</td>"
                        . "<td>" . e($roomLabel) . "</td>"
                        . "<td>" . e($booking->check_in_date?->format('d/m/Y')) . "</td>"
                        . "<td>" . e($booking->check_out_date?->format('d/m/Y')) . "</td>"
                        . "<td>" . e($g->name) . "</td>"
                        . "<td>" . e($g->relation ?? '') . "</td>"
                        . "<td>" . e($g->age ?? '') . "</td>"
                        . "<td>" . e($g->gender ? ucfirst($g->gender) : '') . "</td>"
                        . "<td>" . e($g->nationality ?? 'Indian') . "</td>"
                        . "<td>" . e(BookingGuest::idTypes()[$g->id_type] ?? $g->id_type ?? '') . "</td>"
                        . "<td>" . e($g->id_number ?? '') . "</td>"
                        . "<td>" . ($g->signature ? 'Yes' : 'No') . "</td>"
                        . "<td>" . ($g->id_document_path ? 'Yes' : 'No') . "</td>"
                        . "</tr>";
                }
            }
            echo "</table></body></html>";
        };
        return response()->stream($callback, 200, $headers);
    }

    private function streamCsv(string $filename, array $headerRow, $rows)
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $callback = function () use ($headerRow, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headerRow);
            foreach ($rows as $r) fputcsv($out, $r);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
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

        // Prefetch whole-hotel bookings that overlap the date range
        $hotelId = session('crm_hotel_id');
        $whBookings = \App\Models\Booking::where('hotel_id', $hotelId)
            ->where('is_whole_hotel', true)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<', $to->copy()->addDay()->toDateString())
            ->where('check_out_date', '>', $from->toDateString())
            ->get(['check_in_date', 'check_out_date', 'booking_number', 'customer_id'])
            ->load('customer:id,name');

        $availability = [];
        $cur = $from->copy();
        while ($cur <= $to) {
            $ds = $cur->toDateString();
            $dayData = ['date' => $ds, 'label' => $cur->format('D, d M'), 'slots' => [], 'whole_hotel' => null];

            // Check if a whole-hotel booking covers this date
            $whForDay = $whBookings->first(
                fn($b) => $b->check_in_date->toDateString() <= $ds && $b->check_out_date->toDateString() > $ds
            );

            $roomIds = $rooms->pluck('id')->toArray();
            foreach ($slots as $slot) {
                if ($whForDay) {
                    // Whole hotel booked — all rooms blocked
                    $bookedDetails = $rooms->map(fn($r) => [
                        'room_id'     => $r->id,
                        'room_number' => $r->room_number,
                        'booking_number' => $whForDay->booking_number,
                        'guest_name'  => $whForDay->customer?->name ?? '—',
                        'whole_hotel' => true,
                    ])->values()->all();
                    $dayData['whole_hotel'] = $whForDay->booking_number;
                    $available = 0;
                    $booked    = $total;
                    $freeRooms = [];
                } else {
                    $bookedDetails = $conflictSvc->getConflictingRoomDetails($slot, $ds);
                    $bookedDetails = array_values(array_filter($bookedDetails, fn($d) => in_array($d['room_id'], $roomIds)));
                    $bookedIds     = array_column($bookedDetails, 'room_id');
                    $booked        = count($bookedIds);
                    $available     = $total - $booked;
                    $freeRooms     = $rooms->filter(fn($r) => !in_array($r->id, $bookedIds))->pluck('room_number')->values()->all();
                }

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

        $whBookings = Booking::where('is_whole_hotel', true)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<=', $to->toDateString())
            ->where('check_out_date', '>', $from->toDateString())
            ->with('customer:id,name')
            ->get();
        $whDates = [];
        foreach ($whBookings as $wh) {
            $d   = Carbon::parse($wh->check_in_date)->startOfDay();
            $out = Carbon::parse($wh->check_out_date)->startOfDay();
            while ($d->lessThan($out)) {
                $ds = $d->toDateString();
                if (!isset($whDates[$ds])) {
                    $whDates[$ds] = [
                        'booking_number' => $wh->booking_number,
                        'guest_name'     => $wh->customer->name ?? 'Guest',
                    ];
                }
                $d->addDay();
            }
        }

        if ($request->export === 'csv') {
            return $this->exportSlotAvailabilityCsv($availability, $slots, $from, $to);
        }

        return view('admin.reports.slot-availability', compact('availability', 'slots', 'rooms', 'from', 'to', 'whDates'));
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
                $roomLabel = $booking->is_whole_hotel ? 'Whole Hotel' : ($booking->room?->room_number ?? '');
                fputcsv($out, [
                    $booking->booking_number,
                    $roomLabel,
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
                        $roomLabel,
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

    public function inventoryStock(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        abort_unless(Module::isEnabled('inventory'), 403, 'Inventory module is not enabled for this hotel.');

        $categoryId = $request->input('category_id');
        $onlyLow    = (bool) $request->input('low_only');

        $query = InventoryItem::with('category')->where('is_active', true);
        if ($categoryId) $query->where('category_id', $categoryId);
        if ($onlyLow)    $query->whereRaw('current_stock <= reorder_level AND reorder_level > 0');

        $items = $query->orderBy('name')->get();

        $totals = [
            'count'      => $items->count(),
            'low_count'  => $items->filter(fn($i) => $i->isLowStock())->count(),
            'total_qty'  => (float) $items->sum('current_stock'),
            'total_value'=> (float) $items->sum(fn($i) => (float)$i->current_stock * (float)$i->cost_price),
        ];

        $categories = InventoryCategory::orderBy('name')->get();

        $headers = ['Item', 'Category', 'Stock', 'Unit', 'Reorder Level', 'Cost Price', 'Stock Value', 'Status'];
        $rows = $items->map(fn($i) => [
            $i->name, $i->category->name ?? '',
            number_format((float)$i->current_stock, 2, '.', ''),
            $i->unit,
            number_format((float)$i->reorder_level, 2, '.', ''),
            number_format((float)$i->cost_price, 2, '.', ''),
            number_format((float)$i->current_stock * (float)$i->cost_price, 2, '.', ''),
            $i->isLowStock() ? 'LOW' : 'OK',
        ])->all();

        if ($request->export === 'csv') {
            return $this->streamCsv('inventory-stock-' . now()->format('Ymd') . '.csv', $headers, $rows);
        }
        if ($request->export === 'pdf') {
            return $this->renderReportPdf('Inventory Stock Report', null, null, [
                'Items'       => $totals['count'],
                'Low Stock'   => $totals['low_count'],
                'Total Qty'   => number_format($totals['total_qty'], 2),
                'Stock Value' => '₹' . number_format($totals['total_value'], 2),
            ], $headers, $rows, [0,0,1,0,1,1,1,0], null,
                'inventory-stock-' . now()->format('Ymd') . '.pdf');
        }

        return view('admin.reports.inventory-stock', compact('items', 'totals', 'categories', 'categoryId', 'onlyLow'));
    }

    // ── Performance Analysis ──────────────────────────────────────────────────
    public function performance(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'export'    => 'nullable|in:csv,pdf',
        ]);

        $today = Carbon::today();

        // Date range filter — defaults to last 12 months (current behavior)
        try {
            $from = $request->date_from
                ? Carbon::parse($request->date_from)->startOfDay()
                : $today->copy()->startOfMonth()->subMonths(11);
            $to   = $request->date_to
                ? Carbon::parse($request->date_to)->endOfDay()
                : $today->copy()->endOfMonth();
        } catch (\Exception $e) {
            $from = $today->copy()->startOfMonth()->subMonths(11);
            $to   = $today->copy()->endOfMonth();
        }

        if ($to->lt($from)) { [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()]; }

        // Cap range at 5 years to keep the per-day occupancy loop bounded
        if ($from->copy()->diffInDays($to) > 1830) {
            $from = $to->copy()->subDays(1830)->startOfDay();
        }

        $totalRooms = max(1, (int) Room::count());
        $rangeStart = $from->copy()->startOfDay();
        $rangeEnd   = $to->copy()->endOfDay();
        $rangeDays  = max(1, $rangeStart->copy()->startOfDay()->diffInDays($rangeEnd->copy()->endOfDay()->addSecond()));
        $periodLabel = $from->format('d M Y') . ' – ' . $to->format('d M Y');

        $hotelId = (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
        $cacheParts = [
            'from'  => $rangeStart->toDateString(),
            'to'    => $rangeEnd->toDateString(),
            'rooms' => $totalRooms,
            'days'  => $rangeDays,
        ];

        $build = fn() => $this->buildPerformanceData($rangeStart, $rangeEnd, $rangeDays, $totalRooms);
        $data = $hotelId
            ? AnalyticsCache::remember($hotelId, 'performance', $cacheParts, $build)
            : $build();

        $months          = $data['months'];
        $monthRevenue    = $data['monthRevenue'];
        $monthOccupancy  = $data['monthOccupancy'];
        $monthBookings   = $data['monthBookings'];
        $totalRoomRevenue= $data['totalRoomRevenue'];
        $totalRoomNights = $data['totalRoomNights'];
        $adr             = $data['adr'];
        $revpar          = $data['revpar'];
        $roomTypeLabels  = $data['roomTypeLabels'];
        $roomTypeData    = $data['roomTypeData'];
        $sourceLabels    = $data['sourceLabels'];
        $sourceCounts    = $data['sourceCounts'];
        $dowLabels       = $data['dowLabels'];
        $dowTotals       = $data['dowTotals'];
        $pmLabels        = $data['pmLabels'];
        $pmAmounts       = $data['pmAmounts'];

        // ── Insights (rule-based) ────────────────────────────────────────────
        $insights = $this->buildPerformanceInsights(
            $monthRevenue, $monthOccupancy, $adr, $revpar,
            $sourceLabels, $sourceCounts, $dowLabels, $dowTotals,
            $pmLabels, $pmAmounts, $roomTypeLabels
        );

        // ── Aggregates used by the view & exports ────────────────────────────
        $rev12m = array_sum($monthRevenue);
        $occAvg = count($monthOccupancy) ? round(array_sum($monthOccupancy) / count($monthOccupancy), 1) : 0;

        if ($request->export === 'csv') {
            return $this->exportPerformanceCsv(
                $from, $to, $rev12m, $occAvg, $adr, $revpar, $totalRoomNights,
                $months, $monthRevenue, $monthOccupancy, $monthBookings,
                $roomTypeLabels, $roomTypeData,
                $sourceLabels, $sourceCounts,
                $dowLabels, $dowTotals,
                $pmLabels, $pmAmounts
            );
        }

        if ($request->export === 'pdf') {
            $hotel = \App\Models\Hotel::find(session('crm_hotel_id'));
            $pdf = Pdf::loadView('admin.reports.performance-pdf', [
                'hotel'          => $hotel,
                'from'           => $from,
                'to'             => $to,
                'periodLabel'    => $periodLabel,
                'totalRevenue'   => $rev12m,
                'avgOccupancy'   => $occAvg,
                'adr'            => $adr,
                'revpar'         => $revpar,
                'totalRoomNights'=> $totalRoomNights,
                'months'         => $months,
                'monthRevenue'   => $monthRevenue,
                'monthOccupancy' => $monthOccupancy,
                'monthBookings'  => $monthBookings,
                'roomTypeLabels' => $roomTypeLabels,
                'roomTypeData'   => $roomTypeData,
                'sourceLabels'   => $sourceLabels,
                'sourceCounts'   => $sourceCounts,
                'dowLabels'      => $dowLabels,
                'dowTotals'      => $dowTotals,
                'pmLabels'       => $pmLabels,
                'pmAmounts'      => $pmAmounts,
                'insights'       => $insights,
            ])->setPaper('a4', 'portrait');
            return $pdf->download('performance-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
        }

        return view('admin.reports.performance', compact(
            'months', 'monthRevenue', 'monthOccupancy', 'monthBookings',
            'adr', 'revpar', 'totalRoomRevenue', 'totalRoomNights',
            'roomTypeLabels', 'roomTypeData',
            'sourceLabels', 'sourceCounts',
            'dowLabels', 'dowTotals',
            'pmLabels', 'pmAmounts',
            'insights', 'from', 'to', 'periodLabel', 'rev12m', 'occAvg'
        ));
    }

    private function buildPerformanceData(Carbon $rangeStart, Carbon $rangeEnd, int $rangeDays, int $totalRooms): array
    {
        $months = [];
        $monthRevenue = [];
        $monthOccupancy = [];
        $monthBookings = [];

        // Single payments query for the range, grouped by month
        try {
            $allPayments = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->get(['amount', 'created_at']);
        } catch (\Exception $e) { $allPayments = collect(); }
        $payByMonth = $allPayments->groupBy(fn($p) => Carbon::parse($p->created_at)->format('Y-m'));

        // Single bookings query overlapping the range
        try {
            $allBookings = Booking::whereIn('status', ['confirmed','checked_in','checked_out'])
                ->where('check_in_date',  '<=', $rangeEnd->toDateString())
                ->where('check_out_date', '>',  $rangeStart->toDateString())
                ->get(['check_in_date','check_out_date','is_whole_hotel','total_amount','nights']);
        } catch (\Exception $e) { $allBookings = collect(); }

        // Build per-day occupied-room counts across the window
        $occByDay = [];
        $bkByMonth = [];
        foreach ($allBookings as $b) {
            try {
                $ci = Carbon::parse($b->check_in_date)->startOfDay();
                $co = Carbon::parse($b->check_out_date)->startOfDay();
            } catch (\Exception $e) { continue; }
            $start = $ci->greaterThan($rangeStart) ? $ci->copy() : $rangeStart->copy()->startOfDay();
            $end   = $co->lessThan($rangeEnd->copy()->addDay()->startOfDay()) ? $co->copy() : $rangeEnd->copy()->addDay()->startOfDay();
            $cur = $start->copy();
            $add = $b->is_whole_hotel ? $totalRooms : 1;
            while ($cur->lt($end)) {
                $k = $cur->toDateString();
                $occByDay[$k] = ($occByDay[$k] ?? 0) + $add;
                $cur->addDay();
            }
            $monthCur = $start->copy()->startOfMonth();
            $monthLast = $end->copy()->subDay()->startOfMonth();
            while ($monthCur->lte($monthLast)) {
                $mk = $monthCur->format('Y-m');
                $bkByMonth[$mk] = ($bkByMonth[$mk] ?? 0) + 1;
                $monthCur->addMonth();
            }
        }

        $mIter = $rangeStart->copy()->startOfMonth();
        $mLast = $rangeEnd->copy()->startOfMonth();
        while ($mIter->lte($mLast)) {
            $mStart = $mIter->copy()->startOfMonth();
            $mEnd   = $mIter->copy()->endOfMonth();
            $mk     = $mIter->format('Y-m');
            $months[] = $mIter->format('M Y');

            $monthRevenue[]  = (float) ($payByMonth->get($mk)?->sum('amount') ?? 0);
            $monthBookings[] = (int)   ($bkByMonth[$mk] ?? 0);

            // Clip to the selected range
            $effStart = $mStart->lt($rangeStart) ? $rangeStart->copy()->startOfDay() : $mStart;
            $effEnd   = $mEnd->gt($rangeEnd)     ? $rangeEnd->copy()->startOfDay()   : $mEnd;
            $roomNights = 0; $d = $effStart->copy();
            while ($d->lte($effEnd)) {
                $roomNights += min($totalRooms, $occByDay[$d->toDateString()] ?? 0);
                $d->addDay();
            }
            $effDays = max(1, $effStart->diffInDays($effEnd) + 1);
            $denom = $totalRooms * $effDays;
            $monthOccupancy[] = round(min(100, ($roomNights / max(1,$denom)) * 100), 1);

            $mIter->addMonth();
        }

        // ── ADR / RevPAR over the selected range — overlap-prorated ─────────
        $totalRoomRevenue = 0.0;
        $totalRoomNights  = 0;
        foreach ($allBookings as $b) {
            try {
                $ci = Carbon::parse($b->check_in_date)->startOfDay();
                $co = Carbon::parse($b->check_out_date)->startOfDay();
            } catch (\Exception $e) { continue; }
            $totalNights = max(1, (int)($b->nights ?: $ci->diffInDays($co)));
            $overlapStart = $ci->greaterThan($rangeStart) ? $ci : $rangeStart->copy()->startOfDay();
            $overlapEnd   = $co->lessThan($rangeEnd->copy()->addDay()->startOfDay()) ? $co : $rangeEnd->copy()->addDay()->startOfDay();
            $overlap = max(0, $overlapStart->diffInDays($overlapEnd, false));
            if ($overlap <= 0) continue;
            $rooms = $b->is_whole_hotel ? $totalRooms : 1;
            $totalRoomNights  += $overlap * $rooms;
            $totalRoomRevenue += ((float)$b->total_amount) * ($overlap / $totalNights);
        }
        $adr    = $totalRoomNights > 0 ? round($totalRoomRevenue / $totalRoomNights, 2) : 0;
        $revpar = $totalRooms > 0     ? round($totalRoomRevenue / ($totalRooms * $rangeDays), 2) : 0;

        // ── Slices over the selected range ───────────────────────────────────
        // Room-type donut
        try {
            $typeData = Booking::with('room:id,type')
                ->whereIn('status', ['confirmed','checked_in','checked_out'])
                ->where('check_in_date', '<=', $rangeEnd->toDateString())
                ->where('check_out_date', '>', $rangeStart->toDateString())
                ->get()
                ->groupBy(fn($b) => $b->room->type ?? 'Whole Hotel')
                ->map->count()
                ->sortDesc()
                ->take(8);
        } catch (\Exception $e) { $typeData = collect(); }

        $roomTypeLabels = $typeData->keys()->map(fn($k) => ucfirst((string)$k))->all();
        $roomTypeData   = $typeData->values()->all();

        // Source mix
        try {
            $sourceData = Booking::whereIn('status', ['confirmed','checked_in','checked_out'])
                ->where('check_in_date', '<=', $rangeEnd->toDateString())
                ->where('check_out_date', '>', $rangeStart->toDateString())
                ->get(['source'])
                ->groupBy(fn($b) => $b->source ?: 'Direct')
                ->map->count()
                ->sortDesc()
                ->take(8);
        } catch (\Exception $e) { $sourceData = collect(); }
        $sourceLabels = $sourceData->keys()->map(fn($k) => ucfirst((string)$k))->all();
        $sourceCounts = $sourceData->values()->all();

        // Day-of-week revenue
        try {
            $dowPayments = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->get(['amount', 'created_at']);
        } catch (\Exception $e) { $dowPayments = collect(); }

        $dowLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        $dowTotals = array_fill(0, 7, 0.0);
        foreach ($dowPayments as $p) {
            $idx = (int) Carbon::parse($p->created_at)->dayOfWeekIso - 1; // 0=Mon
            $dowTotals[$idx] += (float) $p->amount;
        }

        // Payment-method breakdown
        try {
            $pmData = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->get(['amount', 'payment_method'])
                ->groupBy(fn($p) => strtolower($p->payment_method ?: 'other'))
                ->map(fn($g) => (float) $g->sum('amount'));
        } catch (\Exception $e) { $pmData = collect(); }
        $pmLabels = $pmData->keys()->map(fn($k) => strtoupper($k))->all();
        $pmAmounts = $pmData->values()->all();

        return [
            'months'           => $months,
            'monthRevenue'     => $monthRevenue,
            'monthOccupancy'   => $monthOccupancy,
            'monthBookings'    => $monthBookings,
            'totalRoomRevenue' => $totalRoomRevenue,
            'totalRoomNights'  => $totalRoomNights,
            'adr'              => $adr,
            'revpar'           => $revpar,
            'roomTypeLabels'   => $roomTypeLabels,
            'roomTypeData'     => $roomTypeData,
            'sourceLabels'     => $sourceLabels,
            'sourceCounts'     => $sourceCounts,
            'dowLabels'        => $dowLabels,
            'dowTotals'        => $dowTotals,
            'pmLabels'         => $pmLabels,
            'pmAmounts'        => $pmAmounts,
        ];
    }

    private function buildPerformanceInsights(
        array $monthRevenue, array $monthOccupancy, $adr, $revpar,
        array $sourceLabels, array $sourceCounts, array $dowLabels, array $dowTotals,
        array $pmLabels, array $pmAmounts, array $roomTypeLabels
    ): array {
        $insights = [];

        // 1. Revenue trend MoM
        $n = count($monthRevenue);
        if ($n >= 2 && $monthRevenue[$n-2] > 0) {
            $delta = (($monthRevenue[$n-1] - $monthRevenue[$n-2]) / $monthRevenue[$n-2]) * 100;
            if ($delta >= 10) {
                $insights[] = ['type'=>'good','icon'=>'fa-arrow-trend-up','title'=>'Revenue is growing',
                    'msg'=>'This month is up '.round($delta,1).'% vs last month. Keep the momentum — consider a small rate increase on peak days.'];
            } elseif ($delta <= -10) {
                $insights[] = ['type'=>'warn','icon'=>'fa-arrow-trend-down','title'=>'Revenue dropped',
                    'msg'=>'This month is down '.round(abs($delta),1).'% vs last month. Try a short-stay promo or review pricing on slow days.'];
            } else {
                $insights[] = ['type'=>'info','icon'=>'fa-equals','title'=>'Revenue is stable',
                    'msg'=>'Movement of '.round($delta,1).'% vs last month. Steady performance — focus on guest satisfaction & repeat visits.'];
            }
        }

        // 2. Occupancy
        $avgOcc = count($monthOccupancy) ? array_sum($monthOccupancy) / count($monthOccupancy) : 0;
        if ($avgOcc < 40) {
            $insights[] = ['type'=>'warn','icon'=>'fa-bed','title'=>'Low average occupancy',
                'msg'=>'Period avg is '.round($avgOcc,1).'%. Push direct booking discounts and OTA visibility.'];
        } elseif ($avgOcc >= 75) {
            $insights[] = ['type'=>'good','icon'=>'fa-bed','title'=>'Strong occupancy',
                'msg'=>'Avg occupancy of '.round($avgOcc,1).'% is excellent. Consider raising weekend/holiday rates to grow ADR.'];
        }

        // 3. ADR / RevPAR
        if ($adr > 0) {
            $insights[] = ['type'=>'info','icon'=>'fa-tag','title'=>'ADR & RevPAR',
                'msg'=>'ADR ₹'.number_format($adr).' · RevPAR ₹'.number_format($revpar).'. RevPAR is the truer profitability metric — track it monthly.'];
        }

        // 4. Source concentration
        if (!empty($sourceCounts)) {
            $totalSrc = array_sum($sourceCounts);
            if ($totalSrc > 0 && $sourceCounts[0] / $totalSrc > 0.7) {
                $insights[] = ['type'=>'warn','icon'=>'fa-share-nodes','title'=>'Channel concentration risk',
                    'msg'=>'Over 70% of bookings come from '.$sourceLabels[0].'. Diversify by enabling more OTAs or pushing direct bookings.'];
            }
        }

        // 5. Best day of week
        if (array_sum($dowTotals) > 0) {
            $bestIdx = array_search(max($dowTotals), $dowTotals);
            $insights[] = ['type'=>'info','icon'=>'fa-calendar-day','title'=>'Best revenue day',
                'msg'=>$dowLabels[$bestIdx].' generates the most revenue. Plan special offers, events or staff coverage around it.'];
        }

        // 6. Cash dominance
        if (!empty($pmAmounts)) {
            $totalPm = array_sum($pmAmounts);
            $cashIdx = array_search('CASH', $pmLabels);
            if ($cashIdx !== false && $totalPm > 0 && $pmAmounts[$cashIdx] / $totalPm > 0.6) {
                $insights[] = ['type'=>'warn','icon'=>'fa-money-bill-wave','title'=>'Cash-heavy collections',
                    'msg'=>'Over 60% of revenue is in cash. Encourage UPI/Card to cut handling risk and improve reconciliation.'];
            }
        }

        // 7. Top room type
        if (!empty($roomTypeLabels)) {
            $insights[] = ['type'=>'good','icon'=>'fa-door-open','title'=>'Most-booked room type',
                'msg'=>$roomTypeLabels[0].' is your top performer for the period. Consider featuring it on your website hero & pricing it as a premium tier.'];
        }

        if (empty($insights)) {
            $insights[] = ['type'=>'info','icon'=>'fa-circle-info','title'=>'No data yet',
                'msg'=>'Once you have a few bookings & payments, this panel will surface improvement ideas.'];
        }

        return $insights;
    }

    private function exportPerformanceCsv(
        $from, $to, $totalRevenue, $avgOccupancy, $adr, $revpar, $totalRoomNights,
        $months, $monthRevenue, $monthOccupancy, $monthBookings,
        $roomTypeLabels, $roomTypeData,
        $sourceLabels, $sourceCounts,
        $dowLabels, $dowTotals,
        $pmLabels, $pmAmounts
    ) {
        $filename = 'performance-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $callback = function () use (
            $from, $to, $totalRevenue, $avgOccupancy, $adr, $revpar, $totalRoomNights,
            $months, $monthRevenue, $monthOccupancy, $monthBookings,
            $roomTypeLabels, $roomTypeData,
            $sourceLabels, $sourceCounts,
            $dowLabels, $dowTotals,
            $pmLabels, $pmAmounts
        ) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Performance Analysis Report']);
            fputcsv($out, ['Period', $from->format('d M Y') . ' to ' . $to->format('d M Y')]);
            fputcsv($out, ['Generated', now()->format('d M Y H:i')]);
            fputcsv($out, []);

            fputcsv($out, ['Key Metrics']);
            fputcsv($out, ['Total Revenue', number_format((float)$totalRevenue, 2, '.', '')]);
            fputcsv($out, ['Average Occupancy %', $avgOccupancy]);
            fputcsv($out, ['ADR', number_format((float)$adr, 2, '.', '')]);
            fputcsv($out, ['RevPAR', number_format((float)$revpar, 2, '.', '')]);
            fputcsv($out, ['Total Room Nights', $totalRoomNights]);
            fputcsv($out, []);

            fputcsv($out, ['Monthly Trend']);
            fputcsv($out, ['Month', 'Revenue', 'Occupancy %', 'Bookings']);
            foreach ($months as $i => $m) {
                fputcsv($out, [
                    $m,
                    number_format((float)($monthRevenue[$i] ?? 0), 2, '.', ''),
                    $monthOccupancy[$i] ?? 0,
                    $monthBookings[$i] ?? 0,
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Bookings by Room Type']);
            fputcsv($out, ['Room Type', 'Bookings']);
            foreach ($roomTypeLabels as $i => $l) fputcsv($out, [$l, $roomTypeData[$i] ?? 0]);
            fputcsv($out, []);

            fputcsv($out, ['Booking Source Mix']);
            fputcsv($out, ['Source', 'Bookings']);
            foreach ($sourceLabels as $i => $l) fputcsv($out, [$l, $sourceCounts[$i] ?? 0]);
            fputcsv($out, []);

            fputcsv($out, ['Revenue by Day of Week']);
            fputcsv($out, ['Day', 'Revenue']);
            foreach ($dowLabels as $i => $l) fputcsv($out, [$l, number_format((float)($dowTotals[$i] ?? 0), 2, '.', '')]);
            fputcsv($out, []);

            fputcsv($out, ['Revenue by Payment Method']);
            fputcsv($out, ['Method', 'Revenue']);
            foreach ($pmLabels as $i => $l) fputcsv($out, [$l, number_format((float)($pmAmounts[$i] ?? 0), 2, '.', '')]);

            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function inventoryMovements(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        abort_unless(Module::isEnabled('inventory'), 403, 'Inventory module is not enabled for this hotel.');

        $from = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $to   = $request->date_to   ? Carbon::parse($request->date_to)   : Carbon::now()->endOfMonth();
        $type = $request->input('type'); // in / out / adjust / null
        $itemId = $request->input('item_id');

        $query = InventoryMovement::with(['item.category', 'creator'])
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        if ($type)   $query->where('type', $type);
        if ($itemId) $query->where('item_id', $itemId);

        $movements = $query->orderByDesc('created_at')->limit(2000)->get();

        $totals = [
            'in'     => (float) $movements->where('type', 'in')->sum('quantity'),
            'out'    => (float) $movements->where('type', 'out')->sum('quantity'),
            'adjust' => (float) $movements->where('type', 'adjust')->sum('quantity'),
            'count'  => $movements->count(),
        ];

        $items = InventoryItem::orderBy('name')->get(['id', 'name', 'unit']);

        $headers = ['Date', 'Item', 'Category', 'Type', 'Quantity', 'Unit', 'By', 'Notes'];
        $rows = $movements->map(fn($m) => [
            Carbon::parse($m->created_at)->format('d/m/Y H:i'),
            $m->item->name ?? '',
            $m->item->category->name ?? '',
            $m->type,
            number_format((float)$m->quantity, 2, '.', ''),
            $m->item->unit ?? '',
            $m->creator->name ?? '',
            $m->notes ?? '',
        ])->all();

        if ($request->export === 'csv') {
            return $this->streamCsv('inventory-movements-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', $headers, $rows);
        }
        if ($request->export === 'pdf') {
            return $this->renderReportPdf('Inventory Movements Report', $from, $to, [
                'Stock In'  => number_format($totals['in'], 2),
                'Stock Out' => number_format($totals['out'], 2),
                'Adjust'    => number_format($totals['adjust'], 2),
                'Entries'   => $totals['count'],
            ], $headers, $rows, [0,0,0,0,1,0,0,0], null,
                'inventory-movements-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
        }

        return view('admin.reports.inventory-movements', compact('movements', 'totals', 'items', 'from', 'to', 'type', 'itemId'));
    }

    private function renderReportPdf(string $title, $from, $to, array $kpis, array $headers, array $rows, array $numeric = [], ?array $totalsRow = null, string $filename = 'report.pdf')
    {
        $hotel  = \App\Models\Hotel::find(session('crm_hotel_id'));
        $period = ($from && $to) ? ($from->format('d M Y') . ' – ' . $to->format('d M Y')) : null;
        $pdf = Pdf::loadView('admin.reports._pdf', compact('title', 'hotel', 'period', 'kpis', 'headers', 'rows', 'numeric', 'totalsRow'))
            ->setPaper('a4', count($headers) > 6 ? 'landscape' : 'portrait');
        return $pdf->download($filename);
    }
}