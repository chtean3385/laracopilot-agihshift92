<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\WhatsAppBillingCycle;
use App\Models\WhatsAppLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsAppBillingController extends Controller
{
    private const RATE = 0.0086; // ₹ per message

    public function index(Request $request)
    {
        // All root hotels (not sub-properties)
        $hotels = Hotel::whereNull('parent_hotel_id')
            ->orWhere('parent_hotel_id', 0)
            ->orderBy('name')
            ->get();

        $now           = Carbon::now();
        $selectedMonth = $request->input('month', $now->format('Y-m')); // "2026-05"
        $periodStart   = Carbon::parse($selectedMonth . '-01')->startOfDay();
        $periodEnd     = $periodStart->copy()->endOfMonth()->endOfDay();
        $periodLabel   = $periodStart->format('F Y');

        // ── Live counts from whatsapp_logs for the selected month ──────────
        $monthlyCounts = WhatsAppLog::where('direction', 'outgoing')
            ->where('event_type', 'message_sent')
            ->where('status', 'ok')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereNotNull('hotel_id')
            ->select('hotel_id', DB::raw('count(*) as cnt'))
            ->groupBy('hotel_id')
            ->pluck('cnt', 'hotel_id');

        // Today & this week counts (always live, for quick reference)
        $todayStart  = $now->copy()->startOfDay();
        $weekStart   = $now->copy()->startOfWeek();

        $todayCounts = WhatsAppLog::where('direction', 'outgoing')
            ->where('event_type', 'message_sent')
            ->where('status', 'ok')
            ->where('created_at', '>=', $todayStart)
            ->whereNotNull('hotel_id')
            ->select('hotel_id', DB::raw('count(*) as cnt'))
            ->groupBy('hotel_id')
            ->pluck('cnt', 'hotel_id');

        $weekCounts = WhatsAppLog::where('direction', 'outgoing')
            ->where('event_type', 'message_sent')
            ->where('status', 'ok')
            ->where('created_at', '>=', $weekStart)
            ->whereNotNull('hotel_id')
            ->select('hotel_id', DB::raw('count(*) as cnt'))
            ->groupBy('hotel_id')
            ->pluck('cnt', 'hotel_id');

        // Existing billing cycles for this month
        $cycles = WhatsAppBillingCycle::where('period_start', $periodStart->toDateString())
            ->pluck(null, 'hotel_id')
            ->map(fn($c) => $c);

        // Available months (distinct months from logs, last 12)
        $availableMonths = WhatsAppLog::where('direction', 'outgoing')
            ->where('event_type', 'message_sent')
            ->where('status', 'ok')
            ->selectRaw("to_char(created_at, 'YYYY-MM') as ym")
            ->groupBy('ym')
            ->orderBy('ym', 'desc')
            ->limit(12)
            ->pluck('ym')
            ->prepend($now->format('Y-m'))
            ->unique()
            ->values();

        // Summary totals for this month
        $totalMessages = $monthlyCounts->sum();
        $totalAmount   = round($totalMessages * self::RATE, 2);
        $totalPaid     = $cycles->where('status', 'paid')->sum('amount');
        $totalUnpaid   = round($totalAmount - $totalPaid, 2);

        return view('platform.whatsapp.billing', compact(
            'hotels', 'monthlyCounts', 'todayCounts', 'weekCounts',
            'cycles', 'selectedMonth', 'periodLabel', 'periodStart', 'periodEnd',
            'availableMonths', 'totalMessages', 'totalAmount', 'totalPaid', 'totalUnpaid'
        ));
    }

    public function markPaid(Request $request, int $hotelId)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'notes' => 'nullable|string|max:500',
        ]);

        $periodStart = Carbon::parse($request->month . '-01')->startOfDay();
        $periodEnd   = $periodStart->copy()->endOfMonth()->endOfDay();
        $periodLabel = $periodStart->format('F Y');

        $count  = WhatsAppLog::where('direction', 'outgoing')
            ->where('event_type', 'message_sent')
            ->where('status', 'ok')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('hotel_id', $hotelId)
            ->count();

        $amount = round($count * self::RATE, 2);

        WhatsAppBillingCycle::updateOrCreate(
            ['hotel_id' => $hotelId, 'period_start' => $periodStart->toDateString()],
            [
                'period_label'     => $periodLabel,
                'period_end'       => $periodEnd->toDateString(),
                'message_count'    => $count,
                'rate_per_message' => self::RATE,
                'amount'           => $amount,
                'status'           => 'paid',
                'paid_at'          => now(),
                'paid_by'          => auth()->user()->name ?? 'Platform Admin',
                'notes'            => $request->notes,
            ]
        );

        return redirect()->route('platform.whatsapp.billing', ['month' => $request->month])
            ->with('success', "Marked as paid for {$periodLabel}.");
    }

    public function markUnpaid(Request $request, int $hotelId)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $periodStart = Carbon::parse($request->month . '-01')->startOfDay();

        WhatsAppBillingCycle::where('hotel_id', $hotelId)
            ->where('period_start', $periodStart->toDateString())
            ->update(['status' => 'unpaid', 'paid_at' => null, 'paid_by' => null]);

        return redirect()->route('platform.whatsapp.billing', ['month' => $request->month])
            ->with('success', 'Marked as unpaid.');
    }

    public function saveLimit(Request $request, int $hotelId)
    {
        $request->validate([
            'wa_daily_limit'   => 'nullable|integer|min:0|max:99999',
            'wa_monthly_limit' => 'nullable|integer|min:0|max:999999',
            'month'            => 'nullable|string',
        ]);

        $hotel = Hotel::findOrFail($hotelId);
        $hotel->update([
            'wa_daily_limit'   => $request->filled('wa_daily_limit')   ? (int)$request->wa_daily_limit   : null,
            'wa_monthly_limit' => $request->filled('wa_monthly_limit') ? (int)$request->wa_monthly_limit : null,
        ]);

        return redirect()->route('platform.whatsapp.billing', ['month' => $request->month])
            ->with('success', "Limits updated for {$hotel->name}.");
    }
}
