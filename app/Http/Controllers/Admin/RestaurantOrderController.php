<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantMenuItem;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantOrderController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    // All orders list
    public function index()
    {
        $orders = RestaurantOrder::with(['table', 'items'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.restaurant.orders', compact('orders'));
    }

    // Single order detail / order taking screen
    public function show($id)
{
    $order = RestaurantOrder::with(['table', 'items.menuItem'])->findOrFail($id);
   if ($order->booking_id) {
    $order->setRelation('booking', \App\Models\Booking::with('customer')->find($order->booking_id));
}
    $categories = \App\Models\RestaurantMenuCategory::with('activeItems')->orderBy('sort_order')->get();
    $bookings   = Booking::where('status', 'checked_in')
        ->with('customer')
        ->get();

    return view('admin.restaurant.order-detail', compact('order', 'categories', 'bookings'));
}

    // Create new order for a table
    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:restaurant_tables,id',
        ]);

        $table = RestaurantTable::findOrFail($request->table_id);

        if ($table->status === 'occupied') {
            // Return existing open order
            $existing = $table->activeOrder;
            if ($existing) {
                return redirect()->route('restaurant.orders.show', $existing->id);
            }
        }

        DB::transaction(function () use ($request, $table, &$order) {
            $order = RestaurantOrder::create([
                'hotel_id'       => $this->hotelId(),
                'table_id'       => $table->id,
                'order_number'   => RestaurantOrder::generateOrderNumber(),
                'status'         => 'open',
                'bill_type'      => 'direct',
                'payment_method' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal'       => 0,
                'tax_rate' => \App\Models\Setting::first()->food_tax_rate ?? 5,
                'tax_amount'     => 0,
                'total'          => 0,
            ]);

            $table->update(['status' => 'occupied']);
        });

        ActivityLogger::log('restaurant_order_created', 'Restaurant', "Order {$order->order_number} opened for {$table->name}");

        return redirect()->route('restaurant.orders.show', $order->id);
    }

    // Update order (link to booking, add notes)
    public function update(Request $request, $id)
    {
        $order = RestaurantOrder::findOrFail($id);

        $order->update([
            'booking_id' => $request->booking_id ?: null,
            'bill_type'  => $request->booking_id ? 'room' : 'direct',
            'notes'      => $request->notes,
        ]);

        return response()->json(['success' => true]);
    }

    // Add item to order
    public function addItem(Request $request, $id)
    {
        $request->validate([
            'menu_item_id' => 'required|exists:restaurant_menu_items,id',
            'quantity'     => 'required|integer|min:1',
            'final_price'  => 'nullable|numeric|min:0',
            'kot_note'     => 'nullable|string|max:200',
        ]);

        $order    = RestaurantOrder::findOrFail($id);
        $menuItem = RestaurantMenuItem::findOrFail($request->menu_item_id);

        $finalPrice = $request->filled('final_price') ? $request->final_price : $menuItem->price;
        $quantity   = $request->quantity;
        $subtotal   = $finalPrice * $quantity;

        $orderItem = RestaurantOrderItem::create([
            'order_id'     => $order->id,
            'menu_item_id' => $menuItem->id,
            'item_name'    => $menuItem->name,
            'unit_price'   => $menuItem->price,
            'final_price'  => $finalPrice,
            'quantity'     => $quantity,
            'subtotal'     => $subtotal,
            'kot_note'     => $request->kot_note,
            'food_type'    => $menuItem->food_type,
        ]);

        // Recalculate order totals
        $this->recalculateTotals($order);

        return response()->json([
            'success'   => true,
            'item'      => $orderItem,
            'order'     => $order->fresh(),
        ]);
    }

    // Remove item from order
    public function removeItem($id, $itemId)
    {
        $order = RestaurantOrder::findOrFail($id);
        RestaurantOrderItem::where('id', $itemId)->where('order_id', $order->id)->delete();
        $this->recalculateTotals($order);

        return response()->json(['success' => true, 'order' => $order->fresh()]);
    }

    // Print KOT — marks order as kotted
    public function printKot($id)
    {
        $order = RestaurantOrder::findOrFail($id);
        $order->update(['status' => 'kotted']);

        return response()->json(['success' => true, 'print_url' => route('restaurant.orders.kot.print', $id)]);
    }

    // KOT print view
    public function kotPrint($id)
    {
        $order = RestaurantOrder::with(['table', 'items'])->findOrFail($id);
        return view('admin.restaurant.kot-print', compact('order'));
    }

    // ── Task #111 — Approve a pending guest QR order ──
    public function approve(Request $request, $id)
    {
        $order = RestaurantOrder::findOrFail($id);

        if (!$order->isPendingApproval()) {
            return back()->with('error', 'This order is not pending approval.');
        }

        $hotelId = (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
        // Multi-tenant safety — booking_id / table_id MUST belong to the
        // current hotel, otherwise a malicious staff user could cross-link
        // an order to a record from a different hotel.
        $request->validate([
            'booking_id' => ['nullable', \Illuminate\Validation\Rule::exists('bookings', 'id')->where('hotel_id', $hotelId)],
            'table_id'   => ['nullable', \Illuminate\Validation\Rule::exists('restaurant_tables', 'id')->where('hotel_id', $hotelId)],
        ]);

        DB::transaction(function () use ($order, $request) {
            $bookingId = $request->booking_id ?: $order->booking_id;
            $tableId   = $request->table_id   ?: $order->table_id;

            // If guest gave a room number and admin didn't override, try to auto-link
            // to the currently checked-in booking on that room.
            if (!$bookingId && $order->room_number) {
                $booking = Booking::where('status', 'checked_in')
                    ->whereHas('room', fn($q) => $q->where('room_number', $order->room_number))
                    ->first();
                if ($booking) {
                    $bookingId = $booking->id;
                }
            }

            $billType = $bookingId ? 'room' : 'direct';

            // If a table is now attached, mark it occupied (skip if it already
            // has another active order — staff should handle that manually).
            if ($tableId) {
                $tbl = RestaurantTable::find($tableId);
                if ($tbl && $tbl->status !== 'occupied') {
                    $tbl->update(['status' => 'occupied']);
                }
            }

            $order->update([
                'approval_status' => 'approved',
                'status'          => 'kotted',
                'booking_id'      => $bookingId,
                'table_id'        => $tableId,
                'bill_type'       => $billType,
            ]);
        });

        ActivityLogger::log('restaurant_order_approved', 'Restaurant', "Guest QR order {$order->order_number} approved");

        return redirect()->route('restaurant.orders.show', $order->id)
            ->with('success', 'Order approved and sent to kitchen.');
    }

    // ── Task #111 — Reject a pending guest QR order ──
    public function reject(Request $request, $id)
    {
        $order = RestaurantOrder::findOrFail($id);

        if (!$order->isPendingApproval()) {
            return back()->with('error', 'This order is not pending approval.');
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:255',
        ]);

        $order->update([
            'approval_status'     => 'rejected',
            'status'              => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason ?: 'Rejected by staff',
        ]);

        ActivityLogger::log('restaurant_order_rejected', 'Restaurant', "Guest QR order {$order->order_number} rejected");

        return redirect()->route('restaurant.index')
            ->with('success', 'Order declined.');
    }

    // Cancel order
    public function cancel($id)
    {
        $order = RestaurantOrder::findOrFail($id);

        if ($order->payment_status === 'paid') {
            return back()->with('error', 'Cannot cancel a paid order.');
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)->update(['status' => 'free']);
            }
        });

        ActivityLogger::log('restaurant_order_cancelled', 'Restaurant', "Order {$order->order_number} cancelled");

        return redirect()->route('restaurant.index')->with('success', 'Order cancelled. Table is now free.');
    }

    // Recalculate order subtotal, tax, total
    private function recalculateTotals(RestaurantOrder $order): void
    {
        $order->refresh();
        $subtotal   = $order->items->sum('subtotal');
        $taxRate    = $order->tax_rate;
        $taxAmount  = round($subtotal * $taxRate / 100, 2);
        $total      = $subtotal + $taxAmount;

        $order->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => $total,
        ]);
    }
}