@extends('layouts.admin')

@section('title', 'Add Inventory Item')

@section('content')
<style>
    .form-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:28px;max-width:600px;}
    .form-title{font-size:1.1rem;font-weight:800;color:#1e293b;margin-bottom:22px;}
    .form-group{margin-bottom:18px;}
    .form-group label{display:block;font-size:.82rem;font-weight:700;color:#374151;margin-bottom:6px;}
    .form-group input,.form-group select,.form-group textarea{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:.88rem;color:#374151;box-sizing:border-box;}
    .form-group input:focus,.form-group select:focus{outline:none;border-color:#0369a1;box-shadow:0 0 0 3px rgba(3,105,161,.1);}
    .form-hint{font-size:.76rem;color:#94a3b8;margin-top:4px;}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
    .toggle-wrap{display:flex;align-items:center;gap:10px;margin-top:6px;}
    .toggle-wrap input[type=checkbox]{width:18px;height:18px;cursor:pointer;}
    .form-footer{display:flex;gap:12px;margin-top:24px;}
    .btn-primary{background:linear-gradient(135deg,#0369a1,#0284c7);color:#fff;border:none;padding:10px 22px;border-radius:9px;font-size:.88rem;font-weight:700;cursor:pointer;}
    .btn-secondary{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:10px 18px;border-radius:9px;font-size:.88rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;}
</style>

<div style="margin-bottom:18px;">
    <a href="{{ route('inventory.index') }}" style="color:#64748b;font-size:.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Inventory</a>
</div>

<div class="form-card">
    <div class="form-title"><i class="fas fa-plus-circle" style="color:#0369a1;margin-right:8px;"></i>Add Inventory Item</div>

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:9px;padding:12px 16px;margin-bottom:18px;color:#dc2626;font-size:.85rem;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('inventory.store') }}">
        @csrf

        <div class="form-group">
            <label>Item Name *</label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Toilet Paper, Rice, Shower Gel" required maxlength="150">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">— No Category —</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Unit *</label>
                <input type="text" name="unit" value="{{ old('unit', 'pcs') }}" placeholder="pcs, kg, litre, box…" required maxlength="30">
                <div class="form-hint">How this item is measured/counted</div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Reorder Level</label>
                <input type="number" name="reorder_level" value="{{ old('reorder_level', 0) }}" min="0" step="0.01" placeholder="0">
                <div class="form-hint">Alert when stock drops to or below this</div>
            </div>
            <div class="form-group">
                <label>Cost Price (₹) per unit</label>
                <input type="number" name="cost_price" value="{{ old('cost_price', 0) }}" min="0" step="0.01" placeholder="0.00">
                <div class="form-hint">Optional — for valuation reports</div>
            </div>
        </div>

        <div class="form-group">
            <label>Active</label>
            <div class="toggle-wrap">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <span style="font-size:.85rem;color:#475569;">Item is visible and usable</span>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn-primary"><i class="fas fa-save" style="margin-right:6px;"></i>Save Item</button>
            <a href="{{ route('inventory.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
