@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div style="margin-bottom:14px;">
    <a href="{{ route('restaurant.orders.index') }}" style="font-size:13px;color:#2563eb;text-decoration:none;">← Back to Orders</a>
</div>

{{-- ═════ TICKET HEADER (KOT-style) ═════ --}}
<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:18px;">
        <div style="min-width:240px;">
            <div style="font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Order Number</div>
            <div style="font-family:'SF Mono',Menlo,monospace;font-size:26px;font-weight:900;color:#0f172a;letter-spacing:.5px;">{{ $order->order_number }}</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                {!! $order->statusBadge() !!}
                @if($order->isGuestQr())
                    <span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;"><i class="fas fa-qrcode"></i> Guest QR</span>
                @endif
                @if($order->isPendingApproval())
                    <span style="background:#ffedd5;color:#9a3412;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;">⏳ Pending approval</span>
                @endif
                @if($order->isPaid())
                    <span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;">✓ Billed to Room</span>
                @endif
            </div>
        </div>

        <div style="display:flex;gap:24px;flex-wrap:wrap;">
            <div>
                <div style="font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Where</div>
                <div style="font-size:18px;font-weight:800;color:#0f172a;margin-top:2px;">
                    @if($order->table)
                        <i class="fas fa-chair" style="color:#64748b;"></i> {{ $order->table->name }}
                    @elseif($order->room_number)
                        <i class="fas fa-door-open" style="color:#64748b;"></i> Room {{ $order->room_number }}
                    @else
                        <span style="color:#94a3b8;">Walk-in</span>
                    @endif
                </div>
            </div>
            <div>
                <div style="font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Started</div>
                <div style="font-size:18px;font-weight:800;color:#0f172a;margin-top:2px;">{{ $order->created_at->format('h:i A') }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $order->created_at->diffForHumans() }}</div>
            </div>
            <div>
                <div style="font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Total</div>
                <div style="font-size:22px;font-weight:900;color:#dc2626;margin-top:2px;">₹{{ number_format($order->total, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Action buttons (only for approved + open orders) --}}
    @if($order->isOpen() && !$order->isPendingApproval())
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px dashed #e2e8f0;">
        <button onclick="printKot()" style="padding:11px 22px;background:#0f172a;color:#fff;border:none;border-radius:10px;font-weight:800;font-size:14px;cursor:pointer;">🖨️ Print KOT</button>
        @if(!$order->isPaid())
        <button onclick="document.getElementById('billModal').classList.remove('hidden')" style="padding:11px 22px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-weight:800;font-size:14px;cursor:pointer;">💳 Generate Bill</button>
        @endif
        <form action="{{ route('restaurant.orders.cancel', $order->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Cancel this order? Table will be freed.')">
            @csrf
            <button type="submit" style="padding:11px 22px;background:#fff;color:#dc2626;border:1.5px solid #fecaca;border-radius:10px;font-weight:800;font-size:14px;cursor:pointer;">✕ Cancel</button>
        </form>
    </div>
    @endif
</div>

{{-- ═════ PENDING APPROVAL PANEL ═════ --}}
@if($order->isPendingApproval())
<div style="background:#fff7ed;border:2px solid #f97316;border-radius:14px;padding:22px 26px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px dashed #fdba74;">
        <div style="width:42px;height:42px;background:#f97316;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-hourglass-half" style="color:#fff;font-size:18px;"></i>
        </div>
        <div>
            <div style="color:#7c2d12;font-size:17px;font-weight:900;">Guest QR order — needs your approval</div>
            <div style="color:#9a3412;font-size:12px;margin-top:2px;">Review the items below, then approve to send to the kitchen.</div>
        </div>
    </div>

    {{-- Guest details grid --}}
    @if($order->guest_name || $order->guest_phone || $order->room_number || $order->table)
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:18px;">
        @if($order->guest_name)
        <div style="background:#fff;padding:12px 14px;border-radius:10px;border:1px solid #fed7aa;">
            <div style="color:#9a3412;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Guest</div>
            <div style="color:#0f172a;font-weight:800;font-size:14px;">{{ $order->guest_name }}</div>
        </div>
        @endif
        @if($order->guest_phone)
        <div style="background:#fff;padding:12px 14px;border-radius:10px;border:1px solid #fed7aa;">
            <div style="color:#9a3412;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Phone</div>
            <div style="color:#0f172a;font-weight:800;font-size:14px;">📞 {{ $order->guest_phone }}</div>
        </div>
        @endif
        @if($order->room_number)
        <div style="background:#fff;padding:12px 14px;border-radius:10px;border:1px solid #fed7aa;">
            <div style="color:#9a3412;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Room</div>
            <div style="color:#0f172a;font-weight:800;font-size:14px;">🛏️ {{ $order->room_number }}</div>
        </div>
        @endif
        @if($order->table)
        <div style="background:#fff;padding:12px 14px;border-radius:10px;border:1px solid #fed7aa;">
            <div style="color:#9a3412;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Table</div>
            <div style="color:#0f172a;font-weight:800;font-size:14px;">🪑 {{ $order->table->name }}</div>
        </div>
        @endif
    </div>
    @endif

    @if($order->guest_notes)
    <div style="background:#fff;border-radius:10px;padding:14px 16px;margin-bottom:18px;border-left:4px solid #f97316;">
        <div style="color:#9a3412;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">📝 Special instructions</div>
        <div style="color:#1e293b;font-size:14px;">{{ $order->guest_notes }}</div>
    </div>
    @endif

    {{-- Action buttons — large + clear --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <form action="{{ route('restaurant.orders.approve', $order->id) }}" method="POST" style="display:flex;flex-direction:column;gap:8px;">
            @csrf
            @if(!$order->booking_id && !$order->room_number)
            <select name="booking_id" style="padding:11px 14px;border:1.5px solid #fdba74;border-radius:10px;background:#fff;font-size:13px;font-weight:600;">
                <option value="">— Direct payment (no room) —</option>
                @foreach($bookings as $b)
                <option value="{{ $b->id }}">{{ $b->booking_number }} · {{ $b->customer?->name }} (Room {{ $b->room?->room_number ?? 'N/A' }})</option>
                @endforeach
            </select>
            @endif
            <button type="submit" style="padding:14px 18px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-weight:900;cursor:pointer;font-size:15px;box-shadow:0 4px 12px rgba(22,163,74,.3);">
                <i class="fas fa-check-circle"></i> Approve &amp; Send to Kitchen
            </button>
        </form>
        <form action="{{ route('restaurant.orders.reject', $order->id) }}" method="POST" style="display:flex;flex-direction:column;gap:8px;" onsubmit="return confirm('Decline this guest order?');">
            @csrf
            <input type="text" name="cancellation_reason" placeholder="Reason (optional)" style="padding:11px 14px;border:1.5px solid #fdba74;border-radius:10px;background:#fff;font-size:13px;">
            <button type="submit" style="padding:14px 18px;background:#dc2626;color:#fff;border:none;border-radius:10px;font-weight:900;cursor:pointer;font-size:15px;box-shadow:0 4px 12px rgba(220,38,38,.3);">
                <i class="fas fa-times-circle"></i> Decline Order
            </button>
        </form>
    </div>
</div>
@endif

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-error mb-4">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT: Menu --}}
    <div class="lg:col-span-2">
        {{-- Link to Room (staff orders only — guest QR uses the approve panel above) --}}
        @if($order->isOpen() && !$order->isPendingApproval() && !$order->isGuestQr())
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm font-medium text-gray-700">🛏️ Link to Room:</span>
                <select id="bookingSelect" class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-0">
                    <option value="">— Direct Payment (no room) —</option>
                    @foreach($bookings as $b)
                    <option value="{{ $b->id }}" {{ $order->booking_id == $b->id ? 'selected' : '' }}>
                        {{ $b->booking_number }} — {{ $b->customer?->name }} (Room {{ $b->room?->room_number ?? 'N/A' }})
                    </option>
                    @endforeach
                </select>
                <button onclick="linkBooking()" class="btn-secondary text-sm">Save</button>
            </div>
            @if($order->booking_id)
            <p class="text-xs text-green-600 mt-2">✅ Linked to room — bill will be added to room invoice</p>
            @endif
        </div>
        @endif

        {{-- Menu Categories — hidden while a guest QR order is awaiting approval --}}
        @if($order->isOpen() && !$order->isPendingApproval())
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
            <h3 class="font-bold text-gray-800 mb-4">📋 Menu</h3>

            {{-- Category tabs --}}
            <div class="flex gap-2 flex-wrap mb-4" id="categoryTabs">
                <button onclick="filterCategory('all')" class="cat-tab active px-3 py-1 rounded-full text-sm border border-gray-300 bg-gray-800 text-white" data-cat="all">All</button>
                @foreach($categories as $cat)
                @if($cat->activeItems->count() > 0)
                <button onclick="filterCategory({{ $cat->id }})"
                    class="cat-tab px-3 py-1 rounded-full text-sm border border-gray-300 hover:bg-gray-100"
                    data-cat="{{ $cat->id }}">{{ $cat->name }}</button>
                @endif
                @endforeach
            </div>

            {{-- Menu Items Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="menuGrid">
                @foreach($categories as $cat)
                @foreach($cat->activeItems as $item)
                <div class="menu-item border border-gray-200 rounded-lg p-3 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all"
                     data-cat="{{ $cat->id }}"
                     onclick="addToOrder({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ $item->food_type }}')">
                    <div class="flex items-start justify-between gap-1">
                        <span class="text-sm font-medium text-gray-800 leading-tight">{{ $item->name }}</span>
                        <span class="text-xs">
                            {{ $item->food_type === 'veg' ? '🟢' : ($item->food_type === 'nonveg' ? '🔴' : '🔵') }}
                        </span>
                    </div>
                    <div class="text-sm font-bold text-blue-600 mt-1">₹{{ number_format($item->price, 2) }}</div>
                    @if($item->description)
                    <div class="text-xs text-gray-400 mt-1 truncate">{{ $item->description }}</div>
                    @endif
                </div>
                @endforeach
                @endforeach
            </div>
        </div>
        @endif

        {{-- Order Items --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h3 class="font-bold text-gray-800 mb-4">🛒 Order Items
                <span class="text-sm font-normal text-gray-500">({{ $order->items->count() }} items)</span>
            </h3>

            @if($order->items->isEmpty())
            <p class="text-gray-400 text-sm text-center py-8">No items added yet. Click menu items above to add.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-gray-600">Item</th>
                            <th class="text-center py-2 text-gray-600">Qty</th>
                            <th class="text-right py-2 text-gray-600">Price</th>
                            <th class="text-right py-2 text-gray-600">Total</th>
                            @if($order->isOpen())
                            <th class="py-2"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="orderItemsBody">
                        @foreach($order->items as $item)
                        <tr class="border-b border-gray-100" id="item-row-{{ $item->id }}">
                            <td class="py-2">
                                <div class="font-medium">
                                    {{ $item->food_type === 'veg' ? '🟢' : ($item->food_type === 'nonveg' ? '🔴' : '🔵') }}
                                    {{ $item->item_name }}
                                </div>
                                @if($item->kot_note)
                                <div class="text-xs text-gray-400">{{ $item->kot_note }}</div>
                                @endif
                            </td>
                            <td class="py-2 text-center">
                                @if($order->isPendingApproval())
                                <form action="{{ route('restaurant.orders.items.qty', [$order->id, $item->id]) }}" method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                                    @csrf @method('PATCH')
                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="99" style="width:54px;padding:3px 6px;border:1px solid #cbd5e1;border-radius:6px;text-align:center;font-size:12px;">
                                    <button type="submit" title="Update quantity" style="padding:3px 7px;background:#0891b2;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer;">↻</button>
                                </form>
                                @else
                                {{ $item->quantity }}
                                @endif
                            </td>
                            <td class="py-2 text-right">₹{{ number_format($item->final_price, 2) }}</td>
                            <td class="py-2 text-right font-medium">₹{{ number_format($item->subtotal, 2) }}</td>
                            @if($order->isOpen())
                            <td class="py-2 text-center">
                                <button onclick="removeItem({{ $item->id }})" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="mt-4 border-t border-gray-200 pt-4 space-y-1 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay">₹{{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>GST ({{ $order->tax_rate }}%)</span>
                    <span id="taxDisplay">₹{{ number_format($order->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-800 text-base border-t border-gray-200 pt-2 mt-2">
                    <span>Total</span>
                    <span id="totalDisplay">₹{{ number_format($order->total, 2) }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- RIGHT: Order Summary --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h3 class="font-bold text-gray-800 mb-3">Order Info</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Order #</span>
                    <span class="font-medium">{{ $order->order_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Table</span>
                    <span class="font-medium">{{ $order->table?->name ?? ($order->room_number ? 'Room ' . $order->room_number : 'Walk-in') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span>{!! $order->statusBadge() !!}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Type</span>
                    <span class="font-medium">{{ $order->booking_id ? '🛏️ Room Bill' : '💵 Direct' }}</span>
                </div>
                @if($order->booking?->customer)
                <div class="flex justify-between">
                    <span class="text-gray-500">Guest</span>
                    <span class="font-medium">{{ $order->booking->customer->name }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500">Started</span>
                    <span class="font-medium">{{ $order->created_at->format('h:i A') }}</span>
                </div>
            </div>
        </div>

        @if($order->isOpen() && !$order->isPendingApproval() && !$order->isPaid())
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
            <p class="font-medium mb-1">Next Steps</p>
            <p>1. Add items from menu</p>
            <p>2. Print KOT for kitchen</p>
            <p>3. Generate bill when done</p>
        </div>
        @elseif($order->isPendingApproval())
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;font-size:13px;color:#9a3412;">
            <p style="font-weight:700;margin-bottom:4px;">⏳ Awaiting your approval</p>
            <p style="font-size:12px;">Use the orange panel to Approve or Decline this guest order.</p>
        </div>
        @elseif($order->isPaid())
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px;font-size:13px;color:#15803d;">
            <p style="font-weight:700;margin-bottom:4px;">✓ Billed to Room</p>
            <p style="font-size:12px;">Charges have been added to the guest's room invoice. You can still print KOT.</p>
        </div>
        @endif
    </div>
</div>

{{-- Add Item Modal --}}
<div id="addItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4">Add Item to Order</h3>
        <div id="addItemName" class="font-medium text-gray-800 mb-1"></div>
        <div id="addItemBasePrice" class="text-sm text-gray-500 mb-4"></div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
            <input type="number" id="addItemQty" value="1" min="1"
                class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Price (editable)</label>
            <input type="number" id="addItemPrice" step="0.01" min="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
            <input type="text" id="addItemNote" placeholder="e.g. no onion, extra spicy"
                class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div class="flex gap-3 justify-end">
            <button onclick="document.getElementById('addItemModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
            <button onclick="confirmAddItem()" class="btn-primary">Add to Order</button>
        </div>
    </div>
</div>

{{-- Bill Modal --}}
<div id="billModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4">💳 Generate Bill</h3>
        <form action="{{ route('restaurant.bills.store') }}" method="POST">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="bill_type" value="direct" checked
                            onchange="togglePaymentMethod(this.value)"> Direct Payment
                    </label>
                    @if($order->booking_id)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="bill_type" value="room"
                            onchange="togglePaymentMethod(this.value)"> Add to Room Bill
                    </label>
                    @endif
                </div>
            </div>

            <div id="paymentMethodSection" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <div class="flex gap-3 flex-wrap">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" checked> Cash
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="card"> Card
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="upi"> UPI
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reference (optional)</label>
                <input type="text" name="payment_reference" placeholder="UPI ref, card last 4..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm">
                <div class="flex justify-between"><span>Subtotal</span><span>₹{{ number_format($order->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span>GST ({{ $order->tax_rate }}%)</span><span>₹{{ number_format($order->tax_amount, 2) }}</span></div>
                <div class="flex justify-between font-bold text-base mt-1 pt-1 border-t border-gray-200">
                    <span>Total</span><span>₹{{ number_format($order->total, 2) }}</span>
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('billModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">✅ Confirm & Generate Bill</button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedMenuItemId = null;

function filterCategory(catId) {
    document.querySelectorAll('.cat-tab').forEach(t => {
        t.classList.remove('bg-gray-800', 'text-white');
        t.classList.add('border-gray-300');
    });
    event.target.classList.add('bg-gray-800', 'text-white');

    document.querySelectorAll('.menu-item').forEach(item => {
        if (catId === 'all' || item.dataset.cat == catId) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function addToOrder(itemId, name, price, foodType) {
    selectedMenuItemId = itemId;
    document.getElementById('addItemName').textContent = name;
    document.getElementById('addItemBasePrice').textContent = 'Base price: ₹' + price.toFixed(2);
    document.getElementById('addItemPrice').value = price;
    document.getElementById('addItemQty').value = 1;
    document.getElementById('addItemNote').value = '';
    document.getElementById('addItemModal').classList.remove('hidden');
}

function confirmAddItem() {
    const qty   = document.getElementById('addItemQty').value;
    const price = document.getElementById('addItemPrice').value;
    const note  = document.getElementById('addItemNote').value;

    fetch('{{ route("restaurant.orders.items.add", $order->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            menu_item_id: selectedMenuItemId,
            quantity: qty,
            final_price: price,
            kot_note: note
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('addItemModal').classList.add('hidden');
            location.reload();
        }
    });
}

function removeItem(itemId) {
    if (!confirm('Remove this item?')) return;

    fetch('{{ url("restaurant/orders/" . $order->id . "/items") }}/' + itemId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function printKot() {
    fetch('{{ route("restaurant.orders.kot", $order->id) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.open(data.print_url, '_blank');
            location.reload();
        }
    });
}

function linkBooking() {
    const bookingId = document.getElementById('bookingSelect').value;
    fetch('{{ route("restaurant.orders.update", $order->id) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ booking_id: bookingId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function togglePaymentMethod(type) {
    document.getElementById('paymentMethodSection').style.display = type === 'room' ? 'none' : '';
}
</script>
@endsection