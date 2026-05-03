<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingExtraCharge;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\InventoryItem;
use App\Models\Module;
use Illuminate\Support\Facades\DB;

class FoodOrderService
{
    /**
     * Approve a food order:
     * 1. Find checked-in booking for room → link it
     * 2. Create a BookingExtraCharge per order item
     * 3. Deduct inventory ingredients if Inventory module is enabled
     */
    public array $deductionWarnings = [];

    public function approve(FoodOrder $order, int $userId): void
    {
        $this->deductionWarnings = [];

        DB::transaction(function () use ($order, $userId) {
            // Lock the order row
            $order = FoodOrder::lockForUpdate()->findOrFail($order->id);

            if ($order->status === 'approved') {
                throw new \Exception('Order is already approved.');
            }
            if ($order->status === 'cancelled') {
                throw new \Exception('Cannot approve a cancelled order.');
            }

            // Find matching checked-in booking for this room
            $booking = Booking::withoutGlobalScopes()
                ->where('hotel_id', $order->hotel_id)
                ->where('status', 'checked_in')
                ->whereHas('room', fn($q) => $q->where('room_number', $order->room_number))
                ->first();

            $order->update([
                'status'      => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'booking_id'  => $booking?->id,
            ]);

            // Bill to room booking if found
            if ($booking) {
                foreach ($order->items as $item) {
                    $totalPrice = round((float) $item->price * $item->quantity, 2);

                    BookingExtraCharge::create([
                        'booking_id'  => $booking->id,
                        'name'        => $item->name,
                        'category'    => 'food',
                        'quantity'    => $item->quantity,
                        'unit_price'  => $item->price,
                        'total_price' => $totalPrice,
                        'notes'       => 'Room Order #' . $order->order_number,
                        'added_by'    => $userId,
                    ]);

                    $booking->increment('total_amount', $totalPrice);
                    $booking->increment('balance_due', $totalPrice);

                    if ($booking->invoice) {
                        $booking->invoice->increment('total_amount', $totalPrice);
                        $booking->invoice->increment('balance', $totalPrice);
                    }
                }
            }

            // Auto-deduct inventory ingredients if module enabled
            if (Module::withoutGlobalScopes()
                ->where('hotel_id', $order->hotel_id)
                ->where('slug', 'inventory')
                ->where('is_enabled', true)
                ->exists()
            ) {
                $this->deductIngredients($order);
            }
        });
    }

    private function deductIngredients(FoodOrder $order): void
    {
        $inventoryService = app(InventoryService::class);

        foreach ($order->items as $orderItem) {
            if (! $orderItem->food_item_id) continue;

            // Load ingredients for this food item
            $ingredients = DB::table('food_item_ingredients')
                ->where('food_item_id', $orderItem->food_item_id)
                ->get();

            foreach ($ingredients as $ingredient) {
                $inventoryItem = InventoryItem::withoutGlobalScopes()
                    ->lockForUpdate()
                    ->find($ingredient->inventory_item_id);

                if (! $inventoryItem) continue;

                $qtyToDeduct = $ingredient->quantity_per_unit * $orderItem->quantity;

                try {
                    $inventoryService->recordMovement($inventoryItem, 'usage', $qtyToDeduct, [
                        'notes'      => 'Auto-deducted for Order #' . $order->order_number,
                        'created_by' => $order->approved_by,
                    ]);
                } catch (\Exception $e) {
                    // Stock may be at 0 / negative — record as a surfaced warning so the
                    // admin sees it after approval (do not roll back the booking charge).
                    \Illuminate\Support\Facades\Log::warning('FoodOrderService: inventory deduction failed', [
                        'order_id'          => $order->id,
                        'inventory_item_id' => $inventoryItem->id,
                        'error'             => $e->getMessage(),
                    ]);
                    $this->deductionWarnings[] = $inventoryItem->name . ': ' . $e->getMessage();
                }
            }
        }
    }
}
