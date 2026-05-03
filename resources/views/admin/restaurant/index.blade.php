@extends('layouts.admin')

@section('title', 'Restaurant')

@section('content')
<div class="content-header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🍽️ Restaurant</h1>
            <p class="text-gray-500 text-sm mt-1">Table map — click a table to open or view order</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @canDo('restaurant.menu')
            <a href="{{ route('restaurant.menu.index') }}" class="btn-secondary">
                📋 Manage Menu
            </a>
            <a href="{{ route('restaurant.qr.index') }}" class="btn-secondary" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                <i class="fas fa-qrcode"></i> QR Codes
            </a>
            @endCanDo
            @canDo('restaurant.billing')
            <a href="{{ route('restaurant.bills.index') }}" class="btn-secondary">
                🧾 Bills
            </a>
            @endCanDo
            @canDo('restaurant.reports')
            <a href="{{ route('restaurant.reports') }}" class="btn-secondary">
                📊 Reports
            </a>
            @endCanDo
            @canDo('restaurant.tables')
            <button onclick="document.getElementById('addTableModal').classList.remove('hidden')" class="btn-primary">
                + Add Table
            </button>
            @endCanDo
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-error mb-4">{{ session('error') }}</div>
@endif

{{-- Pending guest QR orders banner --}}
@canDo('restaurant.orders')
@php
    $pendingGuestOrders = \App\Models\RestaurantOrder::with(['items', 'table'])
        ->where('source', 'guest_qr')
        ->where('approval_status', 'pending')
        ->orderByDesc('created_at')
        ->get();
@endphp
@if($pendingGuestOrders->isNotEmpty())
<div style="background:linear-gradient(135deg,#fff7ed,#fed7aa);border:2px solid #f97316;border-radius:14px;padding:16px 20px;margin-bottom:18px;box-shadow:0 4px 16px rgba(249,115,22,.15);">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <i class="fas fa-bell" style="color:#ea580c;font-size:18px;"></i>
        <strong style="color:#7c2d12;font-size:15px;">{{ $pendingGuestOrders->count() }} guest order{{ $pendingGuestOrders->count() === 1 ? '' : 's' }} waiting for approval</strong>
        <span style="font-size:12px;color:#9a3412;">— scanned from QR</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px;">
        @foreach($pendingGuestOrders as $po)
        <div style="background:#fff;border-radius:10px;padding:12px 14px;border:1px solid #fed7aa;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <strong style="font-size:13px;color:#1e293b;">{{ $po->order_number }}</strong>
                @if($po->room_number)
                    <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">Room {{ $po->room_number }}</span>
                @elseif($po->table)
                    <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">{{ $po->table->name }}</span>
                @else
                    <span style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">Walk-in</span>
                @endif
            </div>
            <div style="font-size:12px;color:#475569;margin-bottom:6px;">{{ $po->items->sum('quantity') }} item(s) · {{ $po->guest_name ?: 'Guest' }} · ₹{{ number_format((float)$po->total, 2) }}</div>
            @if($po->guest_phone)<div style="font-size:11px;color:#94a3b8;margin-bottom:6px;"><i class="fas fa-phone"></i> {{ $po->guest_phone }}</div>@endif
            <a href="{{ route('restaurant.orders.show', $po->id) }}" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;border-radius:6px;text-decoration:none;font-size:12px;font-weight:700;">Review &amp; Approve →</a>
        </div>
        @endforeach
    </div>
</div>
@endif
@endCanDo

{{-- Legend --}}
<div class="flex gap-4 mb-6 flex-wrap">
    <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-green-500 inline-block"></span><span class="text-sm text-gray-600">Free</span></div>
    <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-orange-500 inline-block"></span><span class="text-sm text-gray-600">Occupied</span></div>
    <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-red-500 inline-block"></span><span class="text-sm text-gray-600">Needs Cleaning</span></div>
    <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-gray-800 inline-block"></span><span class="text-sm text-gray-600">Not Available</span></div>
</div>
{{-- Table Grid --}}
@if($tables->isEmpty())
    <div class="text-center py-20 text-gray-400">
        <div class="text-6xl mb-4">🍽️</div>
        <p class="text-lg font-medium">No tables added yet</p>
        <p class="text-sm mt-1">Click "Add Table" to get started</p>
    </div>
@else
<div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
    @foreach($tables as $table)
    @php
        $cardStyle = match($table->status) {
            'free'        => 'border:2px solid #16a34a !important;background:#dcfce7;',
            'occupied'    => 'border:2px solid #ea580c !important;background:#ffedd5;',
            'dirty'       => 'border:2px solid #dc2626 !important;background:#fee2e2;',
            'unavailable' => 'border:2px solid #374151 !important;background:#e5e7eb;opacity:.7;',
            default       => 'border:2px solid #d1d5db !important;background:#fff;',
        };
        $dotStyle = match($table->status) {
            'free'        => 'background:#22c55e;',
            'occupied'    => 'background:#f97316;',
            'dirty'       => 'background:#ef4444;',
            'unavailable' => 'background:#1f2937;',
            default       => 'background:#9ca3af;',
        };
        $labelStyle = match($table->status) {
            'free'        => 'color:#15803d;',
            'occupied'    => 'color:#c2410c;',
            'dirty'       => 'color:#b91c1c;',
            'unavailable' => 'color:#6b7280;',
            default       => 'color:#374151;',
        };
    @endphp
    <div class="border-2 rounded-xl p-4 cursor-pointer transition-all relative group"
         style="{{ $cardStyle }}"
         onclick="handleTableClick({{ $table->id }}, '{{ $table->status }}', {{ $table->activeOrder?->id ?? 'null' }})">

        {{-- Status dot --}}
        <div class="absolute top-3 right-3 w-3 h-3 rounded-full" style="{{ $dotStyle }}"></div>

        {{-- Table name --}}
        <div class="text-lg font-bold text-gray-800 mb-1">{{ $table->name }}</div>
        <div class="text-xs text-gray-500 mb-3">👥 {{ $table->capacity }} seats</div>

        {{-- Status label --}}
        <div class="text-xs font-medium" style="{{ $labelStyle }}">
            {{ $table->statusLabel() }}
        </div>

        {{-- Active order info --}}
        @if($table->activeOrder)
        <div class="mt-2 text-xs text-orange-600 font-medium">
            {{ $table->activeOrder->order_number }}<br>
            ₹{{ number_format($table->activeOrder->total, 2) }}
        </div>
        @endif

        {{-- Edit button (admin only) --}}
        @canDo('restaurant.tables')
        <div class="absolute bottom-2 right-2 hidden group-hover:flex gap-1">
            <button onclick="event.stopPropagation(); openEditTable({{ $table->id }}, '{{ addslashes($table->name) }}', {{ $table->capacity }}, '{{ $table->status }}')"
                class="text-xs bg-white border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">✏️</button>
        </div>
        @endCanDo
    </div>
    @endforeach
</div>
@endif

{{-- Add Table Modal --}}
@canDo('restaurant.tables')
<div id="addTableModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4">Add New Table</h3>
        <form action="{{ route('restaurant.tables.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Table Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" placeholder="e.g. Table 1, VIP Table, Garden Table"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Seating Capacity <span class="text-red-500">*</span></label>
                <input type="number" name="capacity" value="4" min="1" max="50"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('addTableModal').classList.add('hidden')"
                    class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Table</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Table Modal --}}
<div id="editTableModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4">Edit Table</h3>
        <form id="editTableForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Table Name</label>
                <input type="text" name="name" id="editTableName"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input type="number" name="capacity" id="editTableCapacity" min="1" max="50"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="editTableStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="free">✅ Free</option>
                    <option value="dirty">🔴 Needs Cleaning</option>
                    <option value="unavailable">⚫ Not Available</option>
                </select>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('editTableModal').classList.add('hidden')"
                    class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
        {{-- Delete button --}}
        <form id="deleteTableForm" method="POST" class="mt-3">
            @csrf
            @method('DELETE')
            <button type="submit" onclick="return confirm('Delete this table?')"
                class="w-full text-center text-sm text-red-600 hover:text-red-800">🗑️ Delete Table</button>
        </form>
    </div>
</div>
@endCanDo

<script>
function handleTableClick(tableId, status, orderId) {
    if (status === 'unavailable') return;
    if (status === 'dirty') return; // needs cleaning first

    if (status === 'occupied' && orderId) {
        window.location.href = '{{ url("restaurant/orders") }}/' + orderId;
        return;
    }
    

    if (status === 'free') {
        // Create new order
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("restaurant.orders.store") }}';
        form.innerHTML = `
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="table_id" value="${tableId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function openEditTable(id, name, capacity, status) {
    document.getElementById('editTableName').value = name;
    document.getElementById('editTableCapacity').value = capacity;
    document.getElementById('editTableStatus').value = status === 'occupied' ? 'free' : status;
    document.getElementById('editTableForm').action = '/restaurant/tables/' + id;
    document.getElementById('deleteTableForm').action = '/restaurant/tables/' + id;
    document.getElementById('editTableModal').classList.remove('hidden');
}
</script>
@endsection