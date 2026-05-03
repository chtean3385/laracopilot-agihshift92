<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\Module;
use App\Services\ActivityLogger;
use App\Services\FoodOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodOrderController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    private function requireModule(): void
    {
        abort_unless(Module::isEnabled('food-menu'), 403, 'Food Menu module is not enabled for this hotel.');
    }

    public function index(Request $request)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();

        $status = $request->input('status');
        $room   = $request->input('room');
        $from   = $request->input('from');
        $to     = $request->input('to');

        $orders = FoodOrder::with('items')
            ->where('hotel_id', $hotelId)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($room,   fn($q) => $q->where('room_number', $room))
            ->when($from,   fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to,     fn($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $pendingCount = FoodOrder::where('hotel_id', $hotelId)->whereIn('status', ['pending', 'in_progress'])->count();

        return view('admin.food-orders.index', compact('orders', 'status', 'room', 'from', 'to', 'pendingCount'));
    }

    public function show($id)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $order = FoodOrder::with(['items', 'booking', 'approvedBy'])
            ->where('hotel_id', $hotelId)
            ->findOrFail($id);

        // Try to find booking again if not yet linked (still pending)
        $linkedBooking = $order->booking;
        if (! $linkedBooking) {
            $linkedBooking = \App\Models\Booking::where('hotel_id', $hotelId)
                ->where('status', 'checked_in')
                ->whereHas('room', fn($q) => $q->where('room_number', $order->room_number))
                ->with('customer')
                ->first();
        }

        return view('admin.food-orders.show', compact('order', 'linkedBooking'));
    }

    public function status(Request $request, $id)
    {
        $this->requireModule();
        $data = $request->validate([
            'status'              => 'required|in:in_progress,approved,cancelled',
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $order   = FoodOrder::where('hotel_id', $this->hotelId())->findOrFail($id);
        $service = app(FoodOrderService::class);
        $userId  = (int) session('crm_user_id');

        if ($order->status === 'approved') {
            return back()->with('error', 'This order is already approved.');
        }
        if ($order->status === 'cancelled') {
            return back()->with('error', 'This order is already cancelled.');
        }

        if ($data['status'] === 'in_progress') {
            $order->update(['status' => 'in_progress']);
            ActivityLogger::log('food_order_in_progress', 'FoodMenu', "Order #{$order->order_number} marked in progress");
            return back()->with('success', "Order #{$order->order_number} marked as In Progress.");
        }

        if ($data['status'] === 'cancelled') {
            $order->update([
                'status'              => 'cancelled',
                'cancellation_reason' => $data['cancellation_reason'] ?? null,
                'approved_by'         => $userId,
                'approved_at'         => now(),
            ]);
            ActivityLogger::log('food_order_cancelled', 'FoodMenu', "Order #{$order->order_number} cancelled");
            return back()->with('success', "Order #{$order->order_number} cancelled.");
        }

        // Approve: bill to room + auto-deduct ingredients
        try {
            $service->approve($order, $userId);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogger::log('food_order_approved', 'FoodMenu', "Order #{$order->order_number} approved & billed");
        return back()->with('success', "Order #{$order->order_number} approved. Charges added to room {$order->room_number}.");
    }

    // Edit an order item (qty change) — only allowed before approval
    public function editItem(Request $request, $id)
    {
        $this->requireModule();
        $data = $request->validate([
            'item_id'  => 'required|integer',
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $order = FoodOrder::where('hotel_id', $this->hotelId())->findOrFail($id);
        if (in_array($order->status, ['approved', 'cancelled'])) {
            return back()->with('error', 'Cannot edit a completed order.');
        }

        $item = FoodOrderItem::where('order_id', $order->id)->findOrFail($data['item_id']);

        DB::transaction(function () use ($order, $item, $data) {
            if ($data['quantity'] === 0) {
                $item->delete();
            } else {
                $item->update([
                    'quantity' => $data['quantity'],
                    'total'    => round($item->price * $data['quantity'], 2),
                ]);
            }

            $newTotal = FoodOrderItem::where('order_id', $order->id)->sum('total');
            $order->update(['total_amount' => round($newTotal, 2)]);
        });

        ActivityLogger::log('food_order_modified', 'FoodMenu', "Order #{$order->order_number} item modified");
        return back()->with('success', 'Order updated.');
    }

    // Add a new item to an existing pending order
    public function addItem(Request $request, $id)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $data = $request->validate([
            'food_item_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('food_items', 'id')->where('hotel_id', $hotelId),
            ],
            'quantity'     => 'required|integer|min:1|max:99',
        ]);

        $order = FoodOrder::where('hotel_id', $hotelId)->findOrFail($id);
        if (in_array($order->status, ['approved', 'cancelled'])) {
            return back()->with('error', 'Cannot edit a completed order.');
        }

        $foodItem = FoodItem::where('hotel_id', $hotelId)->findOrFail($data['food_item_id']);
        $lineTotal = round((float) $foodItem->price * $data['quantity'], 2);

        DB::transaction(function () use ($order, $foodItem, $data, $lineTotal) {
            $order->items()->create([
                'food_item_id' => $foodItem->id,
                'name'         => $foodItem->name,
                'price'        => $foodItem->price,
                'quantity'     => $data['quantity'],
                'total'        => $lineTotal,
            ]);
            $newTotal = FoodOrderItem::where('order_id', $order->id)->sum('total');
            $order->update(['total_amount' => round($newTotal, 2)]);
        });

        ActivityLogger::log('food_order_item_added', 'FoodMenu', "Item added to Order #{$order->order_number}");
        return back()->with('success', 'Item added.');
    }

    // KOT print (Kitchen Order Ticket)
    public function kotPrint($id)
    {
        $this->requireModule();
        $order = FoodOrder::with('items')->where('hotel_id', $this->hotelId())->findOrFail($id);
        return view('admin.food-orders.kot', compact('order'));
    }

    // Report
    public function report(Request $request)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $room = $request->input('room');
        $status = $request->input('status');
        $export = $request->boolean('export');

        $base = FoodOrder::where('hotel_id', $hotelId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->when($room,   fn($q) => $q->where('room_number', $room))
            ->when($status, fn($q) => $q->where('status', $status));

        $totalOrders  = (clone $base)->count();
        $approved     = (clone $base)->where('status', 'approved');
        $totalRevenue = (clone $approved)->sum('total_amount');
        $avgOrder     = $totalOrders > 0 ? round($totalRevenue / max(1, (clone $approved)->count()), 2) : 0;

        $statusBreakdown = (clone $base)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(total_amount),0) as total'))
            ->groupBy('status')->get();

        $revenueByCategory = DB::table('food_order_items as foi')
            ->join('food_orders as fo', 'fo.id', '=', 'foi.order_id')
            ->leftJoin('food_items as fi', 'fi.id', '=', 'foi.food_item_id')
            ->leftJoin('food_categories as fc', 'fc.id', '=', 'fi.category_id')
            ->where('fo.hotel_id', $hotelId)
            ->where('fo.status', 'approved')
            ->whereDate('fo.created_at', '>=', $from)
            ->whereDate('fo.created_at', '<=', $to)
            ->select(DB::raw("COALESCE(fc.name, 'Uncategorized') as category"), DB::raw('SUM(foi.total) as total'), DB::raw('SUM(foi.quantity) as qty'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $topItems = DB::table('food_order_items as foi')
            ->join('food_orders as fo', 'fo.id', '=', 'foi.order_id')
            ->where('fo.hotel_id', $hotelId)
            ->where('fo.status', 'approved')
            ->whereDate('fo.created_at', '>=', $from)
            ->whereDate('fo.created_at', '<=', $to)
            ->select('foi.name', DB::raw('SUM(foi.quantity) as qty'), DB::raw('SUM(foi.total) as total'))
            ->groupBy('foi.name')
            ->orderByDesc('qty')
            ->limit(20)
            ->get();

        $byRoom = (clone $base)
            ->select('room_number', DB::raw('COUNT(*) as orders'), DB::raw('COALESCE(SUM(total_amount),0) as total'))
            ->where('status', 'approved')
            ->groupBy('room_number')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $dailyTrend = (clone $base)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as orders'), DB::raw('COALESCE(SUM(total_amount),0) as total'))
            ->groupBy('day')->orderBy('day')->get();

        $cancelled = (clone $base)->where('status', 'cancelled')->orderByDesc('created_at')->get();

        if ($export) {
            $filename = "food-orders-report-{$from}_to_{$to}.csv";
            return response()->streamDownload(function () use ($from, $to, $totalOrders, $totalRevenue, $avgOrder, $statusBreakdown, $revenueByCategory, $topItems, $byRoom) {
                $h = fopen('php://output', 'w');
                fputcsv($h, ['Food Orders Report', "{$from} to {$to}"]);
                fputcsv($h, ['']);
                fputcsv($h, ['Summary']);
                fputcsv($h, ['Total Orders', $totalOrders]);
                fputcsv($h, ['Total Revenue (approved)', $totalRevenue]);
                fputcsv($h, ['Average Order Value', $avgOrder]);
                fputcsv($h, ['']);
                fputcsv($h, ['Status', 'Count', 'Total']);
                foreach ($statusBreakdown as $s) fputcsv($h, [$s->status, $s->count, $s->total]);
                fputcsv($h, ['']);
                fputcsv($h, ['Revenue by Category']);
                fputcsv($h, ['Category', 'Quantity', 'Total']);
                foreach ($revenueByCategory as $c) fputcsv($h, [$c->category, $c->qty, $c->total]);
                fputcsv($h, ['']);
                fputcsv($h, ['Top Items']);
                fputcsv($h, ['Item', 'Quantity', 'Total']);
                foreach ($topItems as $i) fputcsv($h, [$i->name, $i->qty, $i->total]);
                fputcsv($h, ['']);
                fputcsv($h, ['Orders by Room']);
                fputcsv($h, ['Room', 'Orders', 'Total']);
                foreach ($byRoom as $r) fputcsv($h, [$r->room_number, $r->orders, $r->total]);
                fclose($h);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return view('admin.food-orders.report', compact(
            'from', 'to', 'room', 'status',
            'totalOrders', 'totalRevenue', 'avgOrder',
            'statusBreakdown', 'revenueByCategory', 'topItems', 'byRoom', 'dailyTrend', 'cancelled'
        ));
    }
}
