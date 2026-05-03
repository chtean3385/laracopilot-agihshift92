@extends('layouts.admin')

@section('title', 'Inventory Categories')

@section('content')
<style>
    .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;}
    .page-title{font-size:1.2rem;font-weight:800;color:#1e293b;margin:0;}
    .btn-primary{background:linear-gradient(135deg,#0369a1,#0284c7);color:#fff;border:none;padding:9px 18px;border-radius:9px;font-size:.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
    .btn-secondary{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:8px 14px;border-radius:9px;font-size:.84rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
    .btn-sm{padding:5px 10px;font-size:.78rem;border-radius:7px;}
    .btn-danger{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;}
    .alert-banner{border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:.88rem;font-weight:600;}
    .alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;}
    .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}
    .add-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;margin-bottom:22px;}
    .add-card-title{font-size:.9rem;font-weight:700;color:#1e293b;margin-bottom:14px;}
    .form-row{display:grid;grid-template-columns:1fr 2fr auto;gap:12px;align-items:end;}
    .form-group label{display:block;font-size:.79rem;font-weight:700;color:#374151;margin-bottom:5px;}
    .form-group input{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;color:#374151;box-sizing:border-box;}
    .table-wrap{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;}
    table{width:100%;border-collapse:collapse;}
    thead th{background:#f8fafc;padding:11px 14px;text-align:left;font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;}
    tbody td{padding:11px 14px;border-bottom:1px solid #f1f5f9;font-size:.85rem;color:#374151;vertical-align:middle;}
    tbody tr:last-child td{border-bottom:none;}
    tbody tr:hover{background:#f8fafc;}
    .item-count{background:#eff6ff;color:#2563eb;padding:2px 10px;border-radius:12px;font-size:.76rem;font-weight:700;}
    .empty-state{text-align:center;padding:50px 20px;color:#94a3b8;}
    .empty-state i{font-size:2.5rem;margin-bottom:12px;display:block;opacity:.4;}
    /* inline edit */
    .edit-row{display:none;background:#f8fafc;}
    .edit-row.open{display:table-row;}
    .edit-form-inner{padding:12px 14px;display:flex;gap:10px;flex-wrap:wrap;align-items:end;}
</style>

<div style="margin-bottom:18px;">
    <a href="{{ route('inventory.index') }}" style="color:#64748b;font-size:.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Inventory</a>
</div>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-tags" style="color:#0369a1;margin-right:8px;"></i>Inventory Categories</h1>
</div>

@if(session('success'))
<div class="alert-banner alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert-banner alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
@endif

@canDo('inventory.create')
<div class="add-card">
    <div class="add-card-title"><i class="fas fa-plus-circle" style="color:#0369a1;margin-right:6px;"></i>Add Category</div>
    <form method="POST" action="{{ route('inventory.categories.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Housekeeping" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="Optional note" maxlength="255">
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Add</button>
        </div>
    </form>
</div>
@endCanDo

<div class="table-wrap">
    @if($categories->isEmpty())
    <div class="empty-state">
        <i class="fas fa-tags"></i>
        <div style="font-size:.95rem;font-weight:700;color:#475569;margin-bottom:4px;">No categories yet</div>
        <div style="font-size:.83rem;">Add a category above to organise your inventory.</div>
    </div>
    @else
    <table>
        <thead>
            <tr>
                <th>Category Name</th>
                <th>Description</th>
                <th>Items</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $cat)
            <tr id="row-{{ $cat->id }}">
                <td style="font-weight:700;color:#1e293b;">{{ $cat->name }}</td>
                <td style="color:#64748b;">{{ $cat->description ?: '—' }}</td>
                <td><span class="item-count">{{ $cat->items_count }} items</span></td>
                <td style="display:flex;gap:6px;">
                    @canDo('inventory.edit')
                    <button class="btn-secondary btn-sm" onclick="toggleEdit({{ $cat->id }})"><i class="fas fa-edit"></i> Edit</button>
                    @endCanDo
                    @canDo('inventory.delete')
                    <form method="POST" action="{{ route('inventory.categories.destroy', $cat->id) }}" onsubmit="return confirm('Delete category \'{{ addslashes($cat->name) }}\'?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-secondary btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    @endCanDo
                </td>
            </tr>
            <tr class="edit-row" id="edit-{{ $cat->id }}">
                <td colspan="4">
                    <div class="edit-form-inner">
                        <form method="POST" action="{{ route('inventory.categories.update', $cat->id) }}" style="display:flex;gap:10px;flex-wrap:wrap;width:100%;align-items:end;">
                            @csrf @method('PUT')
                            <div class="form-group" style="flex:1;min-width:140px;">
                                <label>Name *</label>
                                <input type="text" name="name" value="{{ $cat->name }}" required maxlength="100">
                            </div>
                            <div class="form-group" style="flex:2;min-width:200px;">
                                <label>Description</label>
                                <input type="text" name="description" value="{{ $cat->description }}" maxlength="255">
                            </div>
                            <button type="submit" class="btn-primary" style="margin-bottom:0;"><i class="fas fa-save"></i> Save</button>
                            <button type="button" class="btn-secondary" onclick="toggleEdit({{ $cat->id }})">Cancel</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

<script>
function toggleEdit(id) {
    const row = document.getElementById('edit-' + id);
    row.classList.toggle('open');
}
</script>
@endsection
