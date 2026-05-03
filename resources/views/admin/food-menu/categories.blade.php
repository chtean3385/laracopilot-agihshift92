@extends('layouts.admin')
@section('title', 'Food Categories')

@section('content')
<div style="padding:24px;max-width:1100px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <h1 style="font-size:26px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-tags" style="color:#f97316;"></i> Food Categories</h1>
            <p style="color:#64748b;margin:4px 0 0 0;font-size:14px;">Group menu items into categories like Breakfast, Mains, Beverages.</p>
        </div>
        <a href="{{ route('food-menu.dashboard') }}" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    @if(session('success')) <div style="background:#dcfce7;color:#15803d;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div> @endif
    @if(session('error'))   <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('error') }}</div> @endif
    @if($errors->any())     <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{!! implode('<br>', $errors->all()) !!}</div> @endif

    <div style="background:#fff;border-radius:16px;padding:20px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);">
        <h2 style="font-size:16px;font-weight:800;margin:0 0 14px 0;color:#1e293b;">Add Category</h2>
        <form method="POST" action="{{ route('food-menu.categories.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:2fr 3fr 100px 90px;gap:10px;align-items:end;">
                <div>
                    <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">NAME *</label>
                    <input type="text" name="name" required maxlength="100" style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">DESCRIPTION</label>
                    <input type="text" name="description" maxlength="255" style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">ORDER</label>
                    <input type="number" name="sort_order" value="0" min="0" style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;">
                </div>
                <button type="submit" style="padding:10px 16px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;">Add</button>
            </div>
        </form>
    </div>

    <div style="background:#fff;border-radius:16px;padding:0;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:12px;text-align:left;color:#64748b;font-weight:700;">Name</th>
                    <th style="padding:12px;text-align:left;color:#64748b;font-weight:700;">Description</th>
                    <th style="padding:12px;text-align:center;color:#64748b;font-weight:700;">Items</th>
                    <th style="padding:12px;text-align:center;color:#64748b;font-weight:700;">Order</th>
                    <th style="padding:12px;text-align:center;color:#64748b;font-weight:700;">Status</th>
                    <th style="padding:12px;text-align:right;color:#64748b;font-weight:700;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr style="border-top:1px solid #f1f5f9;">
                    <form method="POST" action="{{ route('food-menu.categories.update', $cat->id) }}">
                        @csrf @method('PUT')
                        <td style="padding:10px 12px;"><input name="name" value="{{ $cat->name }}" required style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;"></td>
                        <td style="padding:10px 12px;"><input name="description" value="{{ $cat->description }}" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;"></td>
                        <td style="padding:10px 12px;text-align:center;color:#64748b;font-weight:700;">{{ $cat->items_count }}</td>
                        <td style="padding:10px 12px;text-align:center;"><input type="number" name="sort_order" value="{{ $cat->sort_order }}" min="0" style="width:60px;padding:7px;border:1.5px solid #e2e8f0;border-radius:8px;text-align:center;"></td>
                        <td style="padding:10px 12px;text-align:center;">
                            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }}>
                                <span style="font-size:12px;color:#64748b;">Active</span>
                            </label>
                        </td>
                        <td style="padding:10px 12px;text-align:right;">
                            <button type="submit" style="padding:7px 12px;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;margin-right:6px;">Save</button>
                    </form>
                            <form method="POST" action="{{ route('food-menu.categories.destroy', $cat->id) }}" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                                @csrf @method('DELETE')
                                <button type="submit" style="padding:7px 10px;background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;"><i class="fas fa-tags" style="font-size:30px;display:block;margin-bottom:10px;"></i>No categories yet — add your first above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
