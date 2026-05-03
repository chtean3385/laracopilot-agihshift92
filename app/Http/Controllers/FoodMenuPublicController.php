<?php

namespace App\Http\Controllers;

use App\Models\FoodCategory;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\Hotel;
use App\Models\Module;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodMenuPublicController extends Controller
{
    private function resolveHotel(string $slug): Hotel
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->first();
        abort_unless($hotel, 404, 'Hotel not found.');

        $enabled = Module::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('slug', 'food-menu')
            ->where('is_enabled', true)
            ->exists();

        abort_unless($enabled, 404, 'Food ordering is not enabled for this hotel.');
        return $hotel;
    }

    private function hotelToken(string $slug): string
    {
        return hash_hmac('sha256', 'food-menu:' . $slug, config('app.key'));
    }

    private function verifyToken(Request $request, string $slug): bool
    {
        $token = $request->input('_menu_token');
        return $token && hash_equals($this->hotelToken($slug), $token);
    }

    public function show(string $slug, ?string $room = null)
    {
        $hotel = $this->resolveHotel($slug);

        $categories = FoodCategory::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $items = FoodItem::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('is_available', true)
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('category_id');

        $setting = Setting::withoutGlobalScopes()->where('hotel_id', $hotel->id)->first();

        return view('public.food-menu.show', [
            'hotel'      => $hotel,
            'categories' => $categories,
            'itemsByCat' => $items,
            'roomNumber' => $room,
            'setting'    => $setting,
            'token'      => $this->hotelToken($slug),
        ]);
    }

    public function order(Request $request, string $slug)
    {
        $hotel = $this->resolveHotel($slug);
        abort_unless($this->verifyToken($request, $slug), 419, 'Invalid token.');

        $data = $request->validate([
            'room_number'  => 'required|string|max:20',
            'guest_name'   => 'nullable|string|max:100',
            'guest_phone'  => 'nullable|string|max:30',
            'guest_notes'  => 'nullable|string|max:500',
            'items'        => 'required|array|min:1',
            'items.*.id'   => 'required|integer',
            'items.*.qty'  => 'required|integer|min:1|max:99',
        ]);

        // Validate items belong to this hotel and are available
        $itemIds = collect($data['items'])->pluck('id')->all();
        $items = FoodItem::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('is_available', true)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if ($items->count() !== count($itemIds)) {
            return back()->withErrors(['items' => 'One or more selected items are no longer available.'])->withInput();
        }

        $order = DB::transaction(function () use ($hotel, $data, $items) {
            $total = 0;
            $rows  = [];
            foreach ($data['items'] as $line) {
                $item   = $items[$line['id']];
                $qty    = (int) $line['qty'];
                $price  = (float) $item->price;
                $lineTotal = round($price * $qty, 2);
                $total += $lineTotal;

                $rows[] = [
                    'food_item_id' => $item->id,
                    'name'         => $item->name,
                    'price'        => $price,
                    'quantity'     => $qty,
                    'total'        => $lineTotal,
                ];
            }

            $order = FoodOrder::create([
                'hotel_id'     => $hotel->id,
                'order_number' => FoodOrder::generateOrderNumber(),
                'room_number'  => $data['room_number'],
                'guest_name'   => $data['guest_name'] ?? null,
                'guest_phone'  => $data['guest_phone'] ?? null,
                'guest_notes'  => $data['guest_notes'] ?? null,
                'status'       => 'pending',
                'total_amount' => round($total, 2),
            ]);

            foreach ($rows as $row) {
                $order->items()->create($row);
            }

            return $order;
        });

        return redirect()->route('public.food-menu.status', [
            'slug'   => $slug,
            'number' => $order->order_number,
        ]);
    }

    public function status(string $slug, string $number)
    {
        $hotel = $this->resolveHotel($slug);

        $order = FoodOrder::withoutGlobalScopes()
            ->with('items')
            ->where('hotel_id', $hotel->id)
            ->where('order_number', $number)
            ->firstOrFail();

        return view('public.food-menu.status', compact('hotel', 'order'));
    }
}
