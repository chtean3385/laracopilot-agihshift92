@extends('layouts.admin')

@section('title', 'Inventory Management')

@section('content')
<style>
    .inv-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;}
    .inv-title{font-size:1.35rem;font-weight:800;color:#1e293b;margin:0;}
    .inv-actions{display:flex;gap:10px;flex-wrap:wrap;}
    .btn-primary{background:linear-gradient(135deg,#0369a1,#0284c7);color:#fff;border:none;padding:9px 18px;border-radius:9px;font-size:.85rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
    .btn-secondary{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:8px 16px;border-radius:9px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
    .btn-sm{padding:5px 11px;font-size:.78rem;border-radius:7px;}
    .btn-danger{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;}
    .btn-green{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;}
    .btn-orange{background:rgba(201,169,110,.08);color:#b08d56;border:1px solid rgba(201,169,110,.2);}
    .btn-blue{background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;}
    .btn-purple{background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;}
    .alert-banner{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;color:#dc2626;font-size:.88rem;font-weight:600;}
    .alert-banner i{font-size:1.1rem;}
    .alert-success{background:#f0fdf4;border-color:#bbf7d0;color:#16a34a;}
    .filters-bar{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;}
    .filters-bar input,.filters-bar select{padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.84rem;color:#374151;background:#fff;min-width:0;}
    .filters-bar input{flex:1;}
    .table-wrap{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;}
    table{width:100%;border-collapse:collapse;}
    thead th{background:#f8fafc;padding:11px 14px;text-align:left;font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;}
    tbody td{padding:11px 14px;border-bottom:1px solid #f1f5f9;font-size:.85rem;color:#374151;vertical-align:middle;}
    tbody tr:last-child td{border-bottom:none;}
    tbody tr:hover{background:#f8fafc;}
    .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;}
    .badge-ok{background:#dcfce7;color:#16a34a;}
    .badge-low{background:#fee2e2;color:#dc2626;animation:pulse-low 2s infinite;}
    .badge-inactive{background:#f1f5f9;color:#94a3b8;}
    @keyframes pulse-low{0%,100%{opacity:1;}50%{opacity:.6;}}
    .cat-chip{background:#eff6ff;color:#2563eb;padding:2px 9px;border-radius:12px;font-size:.75rem;font-weight:600;}
    .actions-cell{display:flex;gap:6px;flex-wrap:wrap;}
    .empty-state{text-align:center;padding:60px 20px;color:#94a3b8;}
    .empty-state i{font-size:3rem;margin-bottom:14px;display:block;opacity:.4;}
    /* Modal */
    .modal-bg{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1000;align-items:center;justify-content:center;}
    .modal-bg.open{display:flex;}
    .modal-box{background:#fff;border-radius:16px;padding:28px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.2);}
    .modal-title{font-size:1rem;font-weight:800;color:#1e293b;margin-bottom:16px;}
    .modal-field{margin-bottom:14px;}
    .modal-field label{display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:5px;}
    .modal-field input,.modal-field select,.modal-field textarea{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;color:#374151;box-sizing:border-box;}
    .modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:18px;}
    .btn-cancel{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:8px 16px;border-radius:8px;font-size:.84rem;font-weight:600;cursor:pointer;}
    .btn-submit{background:linear-gradient(135deg,#0369a1,#0284c7);color:#fff;border:none;padding:9px 18px;border-radius:8px;font-size:.84rem;font-weight:700;cursor:pointer;}
</style>

<div class="inv-header">
    <div>
        <h1 class="inv-title"><i class="fas fa-boxes" style="color:#0369a1;margin-right:8px;"></i>Inventory</h1>
        <div style="font-size:.8rem;color:#94a3b8;margin-top:2px;">Track stock levels, purchases, and usage</div>
    </div>
    <div class="inv-actions">
        @canDo('inventory.create')
        <a href="{{ route('inventory.categories') }}" class="btn-secondary"><i class="fas fa-tag"></i> Categories</a>
        <a href="{{ route('inventory.create') }}" class="btn-primary"><i class="fas fa-plus"></i> Add Item</a>
        @endCanDo
    </div>
</div>

@if(session('success'))
<div class="alert-banner alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert-banner"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
@endif

@if($lowStockCount > 0)
<div class="alert-banner">
    <i class="fas fa-exclamation-triangle"></i>
    <span><strong>{{ $lowStockCount }} item{{ $lowStockCount > 1 ? 's are' : ' is' }} running low on stock.</strong> Review and restock soon.</span>
</div>
@endif

<form method="GET" action="{{ route('inventory.index') }}">
    <div class="filters-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search items…">
        <select name="category_id">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary" style="white-space:nowrap;"><i class="fas fa-search"></i> Filter</button>
        @if($search || $categoryId)
        <a href="{{ route('inventory.index') }}" class="btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="table-wrap">
    @if($items->isEmpty())
    <div class="empty-state">
        <i class="fas fa-boxes"></i>
        <div style="font-size:1rem;font-weight:700;color:#475569;margin-bottom:6px;">No items found</div>
        <div style="font-size:.85rem;">Add your first inventory item to start tracking stock.</div>
        @canDo('inventory.create')
        <a href="{{ route('inventory.create') }}" class="btn-primary" style="margin-top:16px;display:inline-flex;"><i class="fas fa-plus"></i> Add Item</a>
        @endCanDo
    </div>
    @else
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Unit</th>
                <th>Current Stock</th>
                <th>Reorder Level</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td style="font-weight:700;color:#1e293b;">{{ $item->name }}</td>
                <td>
                    @if($item->category)
                    <span class="cat-chip">{{ $item->category->name }}</span>
                    @else
                    <span style="color:#cbd5e1;font-size:.8rem;">—</span>
                    @endif
                </td>
                <td style="color:#64748b;">{{ $item->unit }}</td>
                <td>
                    <span class="badge {{ $item->isLowStock() ? 'badge-low' : 'badge-ok' }}">
                        @if($item->isLowStock())<i class="fas fa-exclamation-triangle"></i>@endif
                        {{ number_format($item->current_stock, 2) }} {{ $item->unit }}
                    </span>
                </td>
                <td style="color:#64748b;">{{ $item->reorder_level > 0 ? number_format($item->reorder_level, 2).' '.$item->unit : '—' }}</td>
                <td>
                    @if($item->is_active)
                    <span class="badge badge-ok">Active</span>
                    @else
                    <span class="badge badge-inactive">Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="actions-cell">
                        @canDo('inventory.create')
                        <button class="btn-secondary btn-sm btn-green inv-btn-purchase"
                            data-name="{{ $item->name }}"
                            data-unit="{{ $item->unit }}"
                            data-url="{{ route('inventory.purchase', $item->id) }}">
                            <i class="fas fa-plus-circle"></i> Purchase
                        </button>
                        @endCanDo
                        @canDo('inventory.adjust')
                        <button class="btn-secondary btn-sm btn-orange inv-btn-usage"
                            data-name="{{ $item->name }}"
                            data-unit="{{ $item->unit }}"
                            data-url="{{ route('inventory.usage', $item->id) }}">
                            <i class="fas fa-minus-circle"></i> Use
                        </button>
                        <button class="btn-secondary btn-sm btn-blue inv-btn-adjust"
                            data-name="{{ $item->name }}"
                            data-unit="{{ $item->unit }}"
                            data-stock="{{ $item->current_stock }}"
                            data-url="{{ route('inventory.adjust', $item->id) }}">
                            <i class="fas fa-sliders-h"></i> Adjust
                        </button>
                        @endCanDo
                        <a href="{{ route('inventory.movements', $item->id) }}" class="btn-secondary btn-sm btn-purple">
                            <i class="fas fa-history"></i> History
                        </a>
                        @canDo('inventory.edit')
                        <a href="{{ route('inventory.edit', $item->id) }}" class="btn-secondary btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endCanDo
                        @canDo('inventory.delete')
                        <form method="POST" action="{{ route('inventory.destroy', $item->id) }}"
                              data-confirm="Delete or deactivate &quot;{{ $item->name }}&quot;?"
                              class="inv-delete-form" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-secondary btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                        @endCanDo
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Purchase Modal --}}
<div class="modal-bg" id="purchaseModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-plus-circle" style="color:#16a34a;margin-right:6px;"></i>Record Purchase — <span id="pm-name"></span></div>
        <form method="POST" id="purchaseForm">
            @csrf
            <div class="modal-field">
                <label>Quantity <span id="pm-unit" style="color:#64748b;font-weight:400;"></span></label>
                <input type="number" name="quantity" min="0.01" step="0.01" required placeholder="e.g. 10">
            </div>
            <div class="modal-field">
                <label>Notes (optional)</label>
                <input type="text" name="notes" placeholder="Supplier, invoice no., etc.">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
                <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#16a34a,#15803d);">Add to Stock</button>
            </div>
        </form>
    </div>
</div>

{{-- Usage/Wastage Modal --}}
<div class="modal-bg" id="usageModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-minus-circle" style="color:#ea580c;margin-right:6px;"></i>Record Usage — <span id="um-name"></span></div>
        <form method="POST" id="usageForm">
            @csrf
            <div class="modal-field">
                <label>Type</label>
                <select name="type">
                    <option value="usage">Usage (normal consumption)</option>
                    <option value="wastage">Wastage (damaged/expired)</option>
                </select>
            </div>
            <div class="modal-field">
                <label>Quantity <span id="um-unit" style="color:#64748b;font-weight:400;"></span></label>
                <input type="number" name="quantity" min="0.01" step="0.01" required placeholder="e.g. 2">
            </div>
            <div class="modal-field">
                <label>Notes (optional)</label>
                <input type="text" name="notes" placeholder="Reason, booking ref., etc.">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
                <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#ea580c,#c2410c);">Deduct Stock</button>
            </div>
        </form>
    </div>
</div>

{{-- Adjust Modal --}}
<div class="modal-bg" id="adjustModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-sliders-h" style="color:#2563eb;margin-right:6px;"></i>Adjust Stock — <span id="am-name"></span></div>
        <form method="POST" id="adjustForm">
            @csrf
            <div class="modal-field">
                <label>Set New Stock Level <span id="am-unit" style="color:#64748b;font-weight:400;"></span></label>
                <input type="number" name="new_stock" id="am-stock" min="0" step="0.01" required placeholder="Enter actual stock count">
            </div>
            <div class="modal-field">
                <label>Reason for adjustment</label>
                <input type="text" name="notes" placeholder="Stock count, correction, etc.">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
                <button type="submit" class="btn-submit">Set Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModals() {
    document.querySelectorAll('.modal-bg').forEach(m => m.classList.remove('open'));
}

document.addEventListener('DOMContentLoaded', function () {
    // Purchase buttons
    document.querySelectorAll('.inv-btn-purchase').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('pm-name').textContent = btn.dataset.name;
            document.getElementById('pm-unit').textContent = '(' + btn.dataset.unit + ')';
            document.getElementById('purchaseForm').action  = btn.dataset.url;
            document.getElementById('purchaseModal').classList.add('open');
        });
    });

    // Usage buttons
    document.querySelectorAll('.inv-btn-usage').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('um-name').textContent = btn.dataset.name;
            document.getElementById('um-unit').textContent = '(' + btn.dataset.unit + ')';
            document.getElementById('usageForm').action    = btn.dataset.url;
            document.getElementById('usageModal').classList.add('open');
        });
    });

    // Adjust buttons
    document.querySelectorAll('.inv-btn-adjust').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('am-name').textContent = btn.dataset.name;
            document.getElementById('am-unit').textContent = '(' + btn.dataset.unit + ')';
            document.getElementById('am-stock').value      = btn.dataset.stock;
            document.getElementById('adjustForm').action   = btn.dataset.url;
            document.getElementById('adjustModal').classList.add('open');
        });
    });

    // Delete forms — confirm from data attribute
    document.querySelectorAll('.inv-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Close modals on backdrop click
    document.querySelectorAll('.modal-bg').forEach(function (m) {
        m.addEventListener('click', function (e) {
            if (e.target === m) closeModals();
        });
    });
});
</script>
@endsection
