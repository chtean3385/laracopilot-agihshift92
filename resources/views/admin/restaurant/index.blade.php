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
    <div class="alert-success mb-4">{!! session('success') !!}</div>
@endif
@if(session('error'))
    <div class="alert-error mb-4">{{ session('error') }}</div>
@endif

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
      $colorClass = match($table->status) {
    'free'        => 'border-green-400 bg-green-50 hover:bg-green-100',
    'occupied'    => 'border-orange-400 bg-orange-50 hover:bg-orange-100',
    'dirty'       => 'border-red-400 bg-red-50 hover:bg-red-100',
    'unavailable' => 'border-gray-700 bg-gray-100 opacity-75',
    default       => 'border-gray-300 bg-white',
};
$dotColor = match($table->status) {
    'free'        => 'bg-green-500',
    'occupied'    => 'bg-orange-500',
    'dirty'       => 'bg-red-500',
    'unavailable' => 'bg-gray-800',
    default       => 'bg-gray-400',
};
    @endphp
    <div class="border-2 rounded-xl p-4 cursor-pointer transition-all {{ $colorClass }} relative group"
         onclick="handleTableClick({{ $table->id }}, '{{ $table->status }}', {{ $table->activeOrder?->id ?? 'null' }})">

        {{-- Status dot --}}
        <div class="absolute top-3 right-3 w-3 h-3 rounded-full {{ $dotColor }}"></div>

        {{-- Table name --}}
        <div class="text-lg font-bold text-gray-800 mb-1">{{ $table->name }}</div>
        <div class="text-xs text-gray-500 mb-3">👥 {{ $table->capacity }} seats</div>

        {{-- Status label --}}
        <div class="text-xs font-medium
            {{ $table->status === 'free' ? 'text-green-700' : '' }}
            {{ $table->status === 'occupied' ? 'text-orange-700' : '' }}
            {{ $table->status === 'unavailable' ? 'text-gray-600' : '' }}">
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