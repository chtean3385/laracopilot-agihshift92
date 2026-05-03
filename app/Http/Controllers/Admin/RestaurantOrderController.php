<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantMenuItem;
use App\Models\Booking;
use App\Models\BookingExtraCharge;
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

        return $this->itemsJson($order);
    }

    // Remove item from order
    public function removeItem($id, $itemId)
    {
        $order = RestaurantOrder::findOrFail($id);
        RestaurantOrderItem::where('id', $itemId)->where('order_id', $order->id)->delete();
        $this->recalculateTotals($order);

        return $this->itemsJson($order);
    }

    // Update an item's quantity (used for editing a guest QR order). Recalculates totals.
    public function updateItemQty(Request $request, $id, $itemId)
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:99']);

        $order = RestaurantOrder::findOrFail($id);
        $item  = RestaurantOrderItem::where('id', $itemId)
            ->where('order_id', $order->id)
            ->firstOrFail();

        $item->update([
            'quantity' => $request->quantity,
            'subtotal' => round((float) $item->final_price * $request->quantity, 2),
        ]);
        $this->recalculateTotals($order);

        if ($request->wantsJson() || $request->ajax()) {
            return $this->itemsJson($order);
        }
        return back()->with('success', 'Quantity updated.');
    }

    // Returns JSON with the re-rendered items partial for AJAX swaps.
    private function itemsJson(RestaurantOrder $order)
    {
        $order = $order->fresh(['items', 'table']);
        $html  = view('admin.restaurant._order_items', ['order' => $order])->render();
        return response()->json([
            'success'    => true,
            'items_html' => $html,
            'subtotal'   => $order->subtotal,
            'tax'        => $order->tax_amount,
            'total'      => $order->total,
        ]);
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

    // Approve a pending guest QR order — auto-bills to room when applicable.
    public function approve(Request $request, $id)
    {
        $order = RestaurantOrder::findOrFail($id);

        if (!$order->isPendingApproval()) {
            return back()->with('error', 'This order is not pending approval.');
        }

        // Multi-tenant safety — booking_id / table_id must belong to this hotel.
        $hotelId = (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
        $request->validate([
            'booking_id' => ['nullable', \Illuminate\Validation\Rule::exists('bookings', 'id')->where('hotel_id', $hotelId)],
            'table_id'   => ['nullable', \Illuminate\Validation\Rule::exists('restaurant_tables', 'id')->where('hotel_id', $hotelId)],
        ]);

        // For a room QR we must end up with a real booking — we never
        // silently downgrade to direct billing.
        $bookingId = $request->booking_id ?: $order->booking_id;
        if (!$bookingId && $order->room_number) {
            $booking = Booking::where('hotel_id', $hotelId)
                ->where('status', 'checked_in')
                ->whereHas('room', fn($q) => $q->where('room_number', $order->room_number))
                ->first();
            $bookingId = $booking?->id;
        }

        if ($order->room_number && !$bookingId) {
            return back()->with(
                'error',
                'No checked-in booking was found for room ' . $order->room_number .
                '. Please attach a booking before approving, or reject the order.'
            );
        }

        DB::transaction(function () use (&$order, $request, $hotelId, $bookingId) {
            // Re-fetch with row lock so a double-click / concurrent approve
            // can't pass the pre-check twice and post duplicate room charges.
            $order = RestaurantOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->approval_status !== 'pending') {
                return; // Already handled by the first request — no-op.
            }

            $tableId  = $request->table_id ?: $order->table_id;
            $billType = $bookingId ? 'room' : 'direct';

            // Mark a newly-attached table as occupied.
            if ($tableId) {
                $tbl = RestaurantTable::find($tableId);
                if ($tbl && $tbl->status !== 'occupied') {
                    $tbl->update(['status' => 'occupied']);
                }
            }

            // Approve + send to kitchen. Status stays 'kotted' so KOT print
            // remains available even after room-billing posts charges.
            $order->update([
                'approval_status' => 'approved',
                'status'          => 'kotted',
                'booking_id'      => $bookingId,
                'table_id'        => $tableId,
                'bill_type'       => $billType,
            ]);

            // Room QR → post each line as a BookingExtraCharge and bump
            // booking + invoice totals (mirrors FoodOrderService).
            if ($billType === 'room' && $bookingId) {
                $booking = Booking::with('invoice')->lockForUpdate()->find($bookingId);
                $order->load('items');

                foreach ($order->items as $item) {
                    $lineTotal = (float) $item->subtotal;

                    BookingExtraCharge::create([
                        'booking_id'  => $bookingId,
                        'name'        => $item->item_name . ($item->kot_note ? ' (' . $item->kot_note . ')' : ''),
                        'category'    => 'restaurant',
                        'quantity'    => $item->quantity,
                        'unit_price'  => $item->final_price,
                        'total_price' => $lineTotal,
                        'notes'       => 'Restaurant Order ' . $order->order_number . ' (guest QR)',
                        'added_by'    => auth()->id(),
                    ]);

                    if ($booking) {
                        $booking->increment('total_amount', $lineTotal);
                        $booking->increment('balance_due',  $lineTotal);
                        if ($booking->invoice) {
                            $booking->invoice->increment('total_amount', $lineTotal);
                            $booking->invoice->increment('balance',      $lineTotal);
                        }
                    }
                }

                // Mark order paid so RestaurantBillController::store refuses
                // to re-post the same charges. Status stays 'kotted'.
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'room',
                    'bill_type'      => 'room',
                    'billed_at'      => now(),
                ]);
            }
        });

        ActivityLogger::log('restaurant_order_approved', 'Restaurant', "Guest QR order {$order->order_number} approved");

        $msg = $bookingId
            ? 'Order approved, sent to kitchen, and billed to the room.'
            : 'Order approved and sent to kitchen.';

        return redirect()->route('restaurant.orders.show', $order->id)->with('success', $msg);
    }

    // Reject a pending guest QR order.
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