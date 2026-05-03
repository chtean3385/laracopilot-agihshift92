<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Module;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantPublicController extends Controller
{
    private function resolveHotel(string $slug): Hotel
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->first();
        abort_unless($hotel, 404, 'Hotel not found.');

        $enabled = Module::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('slug', 'restaurant')
            ->where('is_enabled', true)
            ->exists();

        abort_unless($enabled, 404, 'Restaurant ordering is not enabled for this hotel.');
        return $hotel;
    }

    // Token is bound to the scan context (mode + locked value) so the
    // mode/value cannot be changed by tampering with the form.
    private function token(string $slug, string $mode = 'open', string $value = ''): string
    {
        return hash_hmac('sha256', 'restaurant:' . $slug . ':' . $mode . ':' . $value, config('app.key'));
    }

    private function verifyToken(Request $request, string $slug, string $mode, string $value): bool
    {
        $tok = $request->input('_menu_token');
        return $tok && hash_equals($this->token($slug, $mode, $value), (string) $tok);
    }

    public function show(string $slug)
    {
        // No locked context — guest picks room/table/direct themselves.
        return $this->renderMenu($this->resolveHotel($slug), null, null, 'open', '');
    }

    public function showRoom(string $slug, string $room)
    {
        // Locked to this room.
        return $this->renderMenu($this->resolveHotel($slug), $room, null, 'room', $room);
    }

    public function showTable(string $slug, string $table)
    {
        $hotel = $this->resolveHotel($slug);
        $tableModel = RestaurantTable::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('name', $table)
            ->first();
        // Unknown table → 404; we don't allow locking the QR to a name
        // that doesn't exist (otherwise orders would land with table_id=null).
        abort_unless($tableModel, 404, 'Table not found.');
        return $this->renderMenu($hotel, null, $tableModel, 'table', $table);
    }

    private function renderMenu(Hotel $hotel, ?string $room, RestaurantTable|string|null $table, string $lockMode = 'open', string $lockValue = '')
    {
        $categories = RestaurantMenuCategory::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $items = RestaurantMenuItem::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('is_available', true)
            ->orderBy('category_id')->orderBy('sort_order')->orderBy('name')
            ->get()
            ->groupBy('category_id');

        $setting = Setting::withoutGlobalScopes()->where('hotel_id', $hotel->id)->first();

        // For the unlocked (general) QR page the guest must pick a real
        // room or a real table — we render dropdowns of valid choices
        // server-side instead of a free-text field.
        $availableRooms  = collect();
        $availableTables = collect();
        if ($lockMode === 'open') {
            $availableRooms = Booking::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('status', 'checked_in')
                ->with('room:id,room_number')
                ->get()
                ->pluck('room.room_number')
                ->filter()->unique()->values();

            $availableTables = RestaurantTable::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->orderBy('name')
                ->pluck('name');
        }

        return view('public.restaurant.show', [
            'hotel'           => $hotel,
            'categories'      => $categories,
            'itemsByCat'      => $items,
            'roomNumber'      => $room,
            'table'           => $table,
            'setting'         => $setting,
            'token'           => $this->token($hotel->slug, $lockMode, $lockValue),
            'lockMode'        => $lockMode,   // room | table | open
            'lockValue'       => $lockValue,  // room number or table name (or '')
            'availableRooms'  => $availableRooms,
            'availableTables' => $availableTables,
        ]);
    }

    public function order(Request $request, string $slug)
    {
        $hotel = $this->resolveHotel($slug);

        // The token is bound to the scan context, so we re-derive the
        // expected (mode,value) from the posted lock fields and verify.
        $lockMode  = (string) $request->input('_lock_mode', 'open');
        $lockValue = (string) $request->input('_lock_value', '');
        abort_unless(in_array($lockMode, ['open', 'room', 'table'], true), 419, 'Invalid token.');
        abort_unless($this->verifyToken($request, $slug, $lockMode, $lockValue), 419, 'Invalid token.');

        // Apply the QR lock BEFORE validation — locked room/table pages
        // never need to submit `mode`, the scan context is authoritative.
        if ($lockMode === 'room') {
            $request->merge(['mode' => 'room', 'room_number' => $lockValue]);
        } elseif ($lockMode === 'table') {
            $request->merge(['mode' => 'table', 'table_name' => $lockValue]);
        }

        // Walk-in / direct mode is intentionally not accepted — every
        // scan-to-order must attach to either a room (booking) or a table.
        $data = $request->validate([
            'mode'         => 'required|in:room,table',
            'room_number'  => 'required_if:mode,room|nullable|string|max:20',
            'table_name'   => 'required_if:mode,table|nullable|string|max:50',
            'guest_name'   => 'nullable|string|max:100',
            'guest_phone'  => 'nullable|string|max:30',
            'guest_notes'  => 'nullable|string|max:500',
            'items'        => 'required|array|min:1',
            'items.*.id'   => 'required|integer',
            'items.*.qty'  => 'required|integer|min:1|max:99',
        ]);

        // Validate the chosen room maps to a checked-in booking.
        if ($data['mode'] === 'room') {
            $hasBooking = Booking::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('status', 'checked_in')
                ->whereHas('room', fn($q) => $q->where('room_number', $data['room_number']))
                ->exists();
            if (!$hasBooking) {
                return back()->withErrors([
                    'room_number' => 'Room ' . $data['room_number'] . ' has no active checked-in guest. Please pick a valid room.',
                ])->withInput();
            }
        }

        $itemIds = collect($data['items'])->pluck('id')->all();
        $menuItems = RestaurantMenuItem::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('is_available', true)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== count(array_unique($itemIds))) {
            return back()->withErrors(['items' => 'One or more selected items are no longer available.'])->withInput();
        }

        $tableId = null;
        if ($data['mode'] === 'table') {
            $tbl = RestaurantTable::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('name', $data['table_name'])
                ->first();
            // Table-mode orders MUST attach to a real table session.
            if (!$tbl) {
                return back()->withErrors([
                    'table_name' => 'Table "' . $data['table_name'] . '" was not found. Please pick a valid table or scan the QR on your table.',
                ])->withInput();
            }
            $tableId = $tbl->id;
        }

        $taxRate = (float) (Setting::withoutGlobalScopes()->where('hotel_id', $hotel->id)->value('food_tax_rate') ?? 5);

        $order = DB::transaction(function () use ($hotel, $data, $menuItems, $tableId, $taxRate) {
            $subtotal = 0.0;
            $rows = [];
            foreach ($data['items'] as $line) {
                $mi    = $menuItems[$line['id']];
                $qty   = (int) $line['qty'];
                $price = (float) $mi->price;
                $sub   = round($price * $qty, 2);
                $subtotal += $sub;
                $rows[] = [
                    'menu_item_id' => $mi->id,
                    'item_name'    => $mi->name,
                    'unit_price'   => $price,
                    'final_price'  => $price,
                    'quantity'     => $qty,
                    'subtotal'     => $sub,
                    'food_type'    => $mi->food_type,
                ];
            }
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $total     = round($subtotal + $taxAmount, 2);

            $order = new RestaurantOrder([
                'hotel_id'        => $hotel->id,
                'table_id'        => $tableId,
                'order_number'    => RestaurantOrder::generateOrderNumber(),
                'status'          => 'open',
                'source'          => 'guest_qr',
                'approval_status' => 'pending',
                'bill_type'       => $data['mode'] === 'room' ? 'room' : 'direct',
                'payment_method'  => 'pending',
                'payment_status'  => 'unpaid',
                'subtotal'        => $subtotal,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'room_number'     => $data['mode'] === 'room' ? $data['room_number'] : null,
                'guest_name'      => $data['guest_name'] ?? null,
                'guest_phone'     => $data['guest_phone'] ?? null,
                'guest_notes'     => $data['guest_notes'] ?? null,
            ]);
            $order->save();

            foreach ($rows as $r) {
                $order->items()->create($r);
            }
            return $order;
        });

        return redirect()->route('public.restaurant.status', [
            'slug'   => $slug,
            'number' => $order->order_number,
        ]);
    }

    public function status(string $slug, string $number)
    {
        $hotel = $this->resolveHotel($slug);
        $order = RestaurantOrder::withoutGlobalScopes()
            ->with('items', 'table')
            ->where('hotel_id', $hotel->id)
            ->where('order_number', $number)
            ->firstOrFail();

        return view('public.restaurant.status', compact('hotel', 'order'));
    }
}
