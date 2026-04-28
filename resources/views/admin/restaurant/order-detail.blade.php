@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="content-header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('restaurant.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Tables</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">
                {{ $order->table->name }} — {{ $order->order_number }}
            </h1>
            <p class="text-gray-500 text-sm">
                {!! $order->statusBadge() !!}
                <span class="ml-2">Started {{ $order->created_at->diffForHumans() }}</span>
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($order->isOpen())
            <button onclick="printKot()" class="btn-secondary">🖨️ Print KOT</button>
            <button onclick="document.getElementById('billModal').classList.remove('hidden')" class="btn-primary">
                💳 Generate Bill
            </button>
            @endif
            @if($order->isOpen())
            <form action="{{ route('restaurant.orders.cancel', $order->id) }}" method="POST"
                onsubmit="return confirm('Cancel this order? Table will be freed.')">
                @csrf
                <button type="submit" class="btn-danger">✕ Cancel Order</button>
            </form>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-error mb-4">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT: Menu --}}
    <div class="lg:col-span-2">
        {{-- Link to Room --}}
        @if($order->isOpen())
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

        {{-- Menu Categories --}}
        @if($order->isOpen())
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
                            <td class="py-2 text-center">{{ $item->quantity }}</td>
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
                    <span class="font-medium">{{ $order->table->name }}</span>
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

        @if($order->isOpen())
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
            <p class="font-medium mb-1">Quick Actions</p>
            <p>1. Add items from menu</p>
            <p>2. Print KOT for kitchen</p>
            <p>3. Generate bill when done</p>
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