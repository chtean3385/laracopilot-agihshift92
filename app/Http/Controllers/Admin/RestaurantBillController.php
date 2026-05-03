<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantBill;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\BookingExtraCharge;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantBillController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    // Bills list
    public function index()
    {
        $bills = RestaurantBill::with(['order.table'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.restaurant.bills', compact('bills'));
    }

    // Create bill — settle the order
    public function store(Request $request)
    {
        $request->validate([
            'order_id'          => 'required|exists:restaurant_orders,id',
            'bill_type'         => 'required|in:direct,room',
            'payment_method'    => 'required_if:bill_type,direct|nullable|in:cash,card,upi',
            'payment_reference' => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:500',
        ]);

        $order = RestaurantOrder::with('items')->findOrFail($request->order_id);

        if ($order->payment_status === 'paid') {
            return back()->with('error', 'This order is already billed.');
        }

        if ($order->items->count() === 0) {
            return back()->with('error', 'Cannot bill an empty order. Add items first.');
        }

        DB::transaction(function () use ($request, $order, &$bill) {
            $hotelId = $this->hotelId();

            // Create the bill
            $bill = RestaurantBill::create([
                'hotel_id'          => $hotelId,
                'order_id'          => $order->id,
                'booking_id'        => $request->bill_type === 'room' ? $order->booking_id : null,
                'bill_number'       => RestaurantBill::generateBillNumber(),
                'bill_type'         => $request->bill_type,
                'payment_method'    => $request->bill_type === 'room' ? 'room' : $request->payment_method,
                'subtotal'          => $order->subtotal,
                'tax_rate'          => $order->tax_rate,
                'tax_amount'        => $order->tax_amount,
                'total'             => $order->total,
                'payment_reference' => $request->payment_reference,
                'notes'             => $request->notes,
                'paid_at'           => now(),
            ]);

            // Update order status
            $order->update([
                'status'         => 'billed',
                'payment_status' => 'paid',
                'payment_method' => $bill->payment_method,
                'bill_type'      => $request->bill_type,
                'billed_at'      => now(),
                'booking_id'     => $request->bill_type === 'room' ? $order->booking_id : $order->booking_id,
            ]);

            // Free the table
            RestaurantTable::where('id', $order->table_id)->update(['status' => 'dirty']);

            // If room charge — add as booking extra charge so it shows on hotel invoice
            if ($request->bill_type === 'room' && $order->booking_id) {
               foreach ($order->items as $item) {
                        BookingExtraCharge::create([
                            'booking_id'  => $order->booking_id,
                            'name'        => $item->item_name . ($item->kot_note ? ' (' . $item->kot_note . ')' : ''),
                            'category'    => 'restaurant',
                            'quantity'    => $item->quantity,
                            'unit_price'  => $item->final_price,
                            'total_price' => $item->subtotal,
                            'notes'       => 'Restaurant Order ' . $order->order_number,
                        ]);
                    }
                
            }
        });

        ActivityLogger::log('restaurant_bill_created', 'Restaurant', "Bill {$bill->bill_number} created for order {$order->order_number}");

        return redirect()->route('restaurant.index')
            ->with('success', "Bill {$bill->bill_number} created. Table is now free.")
            ->with('print_url', route('restaurant.bills.print', $bill->id));
    }

    // Show bill detail
    public function show($id)
    {
        $bill = RestaurantBill::with(['order.items', 'order.table'])->findOrFail($id);
        return view('admin.restaurant.bill-detail', compact('bill'));
    }

    // Print bill
   public function print($id)
{
    $bill = RestaurantBill::with(['order.items', 'order.table'])->findOrFail($id);
    if ($bill->booking_id) {
        $bill->setRelation('booking', \App\Models\Booking::with('customer')->find($bill->booking_id));
    }
    $setting = \App\Models\Setting::first();
    return view('admin.restaurant.bill-print', compact('bill', 'setting'));
}
}