@extends('layouts.admin')

@section('title', 'Menu Management')

@section('content')
<div class="content-header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('restaurant.index') }}" class="text-sm hover:underline" style="color: #c9a96e;">← Back to Tables</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">📋 Menu Management</h1>
            <p class="text-gray-500 text-sm">Manage categories and menu items</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('restaurant.qr.index') }}" class="btn-secondary" style="background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;">
                <i class="fas fa-qrcode"></i> Print QR Codes
            </a>
            <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')" class="btn-secondary">
                + Add Category
            </button>
            <button onclick="document.getElementById('addItemModal').classList.remove('hidden')" class="btn-primary">
                + Add Menu Item
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-error mb-4">{{ session('error') }}</div>
@endif

{{-- Menu Categories --}}
@if($categories->isEmpty())
<div class="text-center py-20 text-gray-400">
    <div class="text-6xl mb-4">📋</div>
    <p class="text-lg font-medium">No categories yet</p>
    <p class="text-sm mt-1">Add a category first, then add menu items</p>
</div>
@else
@foreach($categories as $category)
<div class="bg-white rounded-xl border border-gray-200 mb-4 overflow-hidden">
    {{-- Category Header --}}
    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <span class="font-bold text-gray-800">{{ $category->name }}</span>
            <span class="text-xs text-gray-500 bg-gray-200 rounded-full px-2 py-0.5">
                {{ $category->items->count() }} items
            </span>
        </div>
        <div class="flex gap-2">
            <button onclick="openEditCategory({{ $category->id }}, '{{ addslashes($category->name) }}')"
                class="text-xs hover:underline" style="color: #c9a96e;">Edit</button>
            <form action="{{ route('restaurant.menu.categories.destroy', $category->id) }}" method="POST"
                onsubmit="return confirm('Delete this category? Only works if no items exist.')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
            </form>
        </div>
    </div>

    {{-- Items --}}
    @if($category->items->isEmpty())
    <div class="px-4 py-6 text-center text-gray-400 text-sm">No items in this category</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left px-4 py-2 text-gray-600" style="width:64px;">Photo</th>
                    <th class="text-left px-4 py-2 text-gray-600">Item</th>
                    <th class="text-center px-4 py-2 text-gray-600">Type</th>
                    <th class="text-right px-4 py-2 text-gray-600">Price</th>
                    <th class="text-center px-4 py-2 text-gray-600">Available</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($category->items as $item)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-3">
                        @if($item->imageUrl())
                        <img src="{{ $item->imageUrl() }}" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;">
                        @else
                        <div style="width:48px;height:48px;border-radius:8px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#dc2626;border:1px dashed #fecaca;"><i class="fas fa-utensils"></i></div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800">{{ $item->name }}</div>
                        @if($item->description)
                        <div class="text-xs text-gray-400">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($item->food_type === 'veg')
                            <span class="text-green-600">🟢 Veg</span>
                        @elseif($item->food_type === 'nonveg')
                            <span class="text-red-600">🔴 Non-Veg</span>
                        @else
                            <span style="color: #7a8a9a;">🔵 Beverage</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800">
                        ₹{{ number_format($item->price, 2) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="toggleItem({{ $item->id }}, this)"
                            class="toggle-btn px-2 py-1 rounded-full text-xs font-medium
                            {{ $item->is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">

                            {{ $item->is_available ? 'Available' : 'Unavailable' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="openEditItem({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ $item->food_type }}', '{{ addslashes($item->description ?? '') }}', {{ $item->category_id ?? 'null' }}, '{{ $item->imageUrl() ?? '' }}')"
                            class="text-xs hover:underline mr-2" style="color: #c9a96e;">Edit</button>
                        <form action="{{ route('restaurant.menu.items.destroy', $item->id) }}" method="POST"
                            class="inline" onsubmit="return confirm('Delete this item?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endforeach
@endif

{{-- Add Category Modal --}}
<div id="addCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4">Add Category</h3>
        <form action="{{ route('restaurant.menu.categories.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                <input type="text" name="name" placeholder="e.g. Starters, Mains, Drinks, Desserts"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')"
                    class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Category Modal --}}
<div id="editCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4">Edit Category</h3>
        <form id="editCategoryForm" method="POST">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                <input type="text" name="name" id="editCategoryName"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')"
                    class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Item Modal --}}
<div id="addItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg mx-4">
        <h3 class="text-lg font-bold mb-4">Add Menu Item</h3>
        <form action="{{ route('restaurant.menu.items.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Photo (optional)</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" style="font-size:13px;">
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG or WebP — max 2 MB</p>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" placeholder="e.g. Paneer Tikka"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" placeholder="0.00"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                    <select name="food_type" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        <option value="veg">🟢 Veg</option>
                        <option value="nonveg">🔴 Non-Veg</option>
                        <option value="beverage">🔵 Beverage</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">— No Category —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <input type="text" name="description" placeholder="Brief description"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')"
                    class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Item</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Item Modal --}}
<div id="editItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg mx-4">
        <h3 class="text-lg font-bold mb-4">Edit Menu Item</h3>
        <form id="editItemForm" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2" id="editCurrentImageWrap" style="display:none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Photo</label>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img id="editCurrentImage" src="" alt="" style="width:64px;height:64px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;">
                        <label style="font-size:13px;color:#475569;cursor:pointer;">
                            <input type="checkbox" name="remove_image" value="1" style="margin-right:6px;"> Remove photo
                        </label>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Replace / Add Photo (optional)</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" style="font-size:13px;">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                    <input type="text" name="name" id="editItemName"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                    <input type="number" name="price" id="editItemPrice" step="0.01" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="food_type" id="editItemFoodType"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="veg">🟢 Veg</option>
                        <option value="nonveg">🔴 Non-Veg</option>
                        <option value="beverage">🔵 Beverage</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="editItemCategory"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">— No Category —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" id="editItemDescription"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('editItemModal').classList.add('hidden')"
                    class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCategory(id, name) {
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editCategoryForm').action = '{{ url("restaurant/menu/categories") }}/' + id;
    document.getElementById('editCategoryModal').classList.remove('hidden');
}

function openEditItem(id, name, price, foodType, description, categoryId, imageUrl) {
    document.getElementById('editItemName').value = name;
    document.getElementById('editItemPrice').value = price;
    document.getElementById('editItemFoodType').value = foodType;
    document.getElementById('editItemDescription').value = description;
    document.getElementById('editItemCategory').value = categoryId || '';
    document.getElementById('editItemForm').action = '{{ url("restaurant/menu/items") }}/' + id;
    var wrap = document.getElementById('editCurrentImageWrap');
    if (imageUrl) {
        document.getElementById('editCurrentImage').src = imageUrl;
        wrap.style.display = '';
    } else {
        wrap.style.display = 'none';
    }
    document.getElementById('editItemModal').classList.remove('hidden');
}

function toggleItem(id, btn) {
    fetch('{{ url("restaurant/menu/items") }}/' + id + '/toggle', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.textContent = data.is_available ? 'Available' : 'Unavailable';
            btn.className = 'toggle-btn px-2 py-1 rounded-full text-xs font-medium ' +
                (data.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
        }
    });
}
</script>
@endsection