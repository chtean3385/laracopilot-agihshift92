@extends('layouts.admin')
@section('title', $item ? 'Edit Item' : 'Add Item')

@section('content')
<div style="padding:24px;max-width:760px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h1 style="font-size:24px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-utensils" style="color:#f97316;"></i> {{ $item ? 'Edit Item' : 'Add Menu Item' }}</h1>
        <a href="{{ route('food-menu.dashboard') }}" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    @if($errors->any()) <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{!! implode('<br>', $errors->all()) !!}</div> @endif

    <form method="POST" action="{{ $item ? route('food-menu.items.update', $item->id) : route('food-menu.items.store') }}" enctype="multipart/form-data" style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);">
        @csrf @if($item) @method('PUT') @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
            <div>
                <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">ITEM NAME *</label>
                <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" required maxlength="150" style="width:100%;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">CATEGORY</label>
                <select name="category_id" style="width:100%;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fff;">
                    <option value="">— Uncategorized —</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (old('category_id', $item->category_id ?? null) == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">DESCRIPTION</label>
            <textarea name="description" maxlength="1000" rows="3" style="width:100%;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;">{{ old('description', $item->description ?? '') }}</textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:14px;">
            <div>
                <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">PRICE (₹) *</label>
                <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $item->price ?? '') }}" required style="width:100%;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">SORT ORDER</label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $item->sort_order ?? 0) }}" style="width:100%;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;">
            </div>
            <div style="display:flex;align-items:center;padding-top:24px;">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', $item->is_available ?? true) ? 'checked' : '' }} style="width:18px;height:18px;cursor:pointer;">
                    <span style="font-size:14px;font-weight:600;color:#475569;">Available for order</span>
                </label>
            </div>
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">PHOTO (optional, max 2MB)</label>
            @if($item && $item->image_path)
            <div style="margin-bottom:8px;"><img src="{{ $item->imageUrl() }}" style="max-width:160px;border-radius:10px;border:1.5px solid #e2e8f0;"></div>
            @endif
            <input type="file" name="image" accept="image/*" style="width:100%;padding:11px;border:1.5px dashed #cbd5e1;border-radius:10px;font-size:13px;background:#f8fafc;">
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" style="padding:12px 22px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:10px;font-weight:800;cursor:pointer;font-size:14px;">
                <i class="fas fa-save"></i> {{ $item ? 'Save Changes' : 'Add Item' }}
            </button>
            @if($item)
            <button type="button" onclick="document.getElementById('toggleForm').submit();" style="padding:12px 18px;background:#fff;color:{{ $item->is_available ? '#b45309' : '#15803d' }};border:1.5px solid #e2e8f0;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;">
                {{ $item->is_available ? 'Disable' : 'Enable' }}
            </button>
            <button type="button" onclick="document.getElementById('deleteForm').submit();" style="padding:12px 18px;background:#fee2e2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;"><i class="fas fa-trash"></i> Delete</button>
            @endif
            <a href="{{ route('food-menu.dashboard') }}" style="padding:12px 18px;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:10px;font-weight:700;text-decoration:none;font-size:13px;">Cancel</a>
        </div>
    </form>

    @if($item)
    <form id="toggleForm" method="POST" action="{{ route('food-menu.items.toggle', $item->id) }}" style="display:none;">@csrf</form>
    <form id="deleteForm" method="POST" action="{{ route('food-menu.items.destroy', $item->id) }}" style="display:none;" onsubmit="return confirm('Delete this item?');">@csrf @method('DELETE')</form>
    @endif
</div>
@endsection
