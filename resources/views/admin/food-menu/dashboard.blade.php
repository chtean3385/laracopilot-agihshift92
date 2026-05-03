@extends('layouts.admin')
@section('title', 'Food Menu')

@section('content')
<div style="padding:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <h1 style="font-size:28px;font-weight:800;color:#1e293b;margin:0;display:flex;align-items:center;gap:12px;">
                <i class="fas fa-utensils" style="color:#f97316;"></i> Food Menu &amp; Orders
            </h1>
            <p style="color:#64748b;margin:6px 0 0 0;font-size:14px;">Manage your in-room food menu and incoming guest orders.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('food-menu.qr') }}" style="display:inline-flex;align-items:center;gap:8px;padding:11px 18px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;">
                <i class="fas fa-qrcode"></i> Print QR Codes
            </a>
            <a href="{{ route('food-menu.categories') }}" style="display:inline-flex;align-items:center;gap:8px;padding:11px 18px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="{{ route('food-menu.items.create') }}" style="display:inline-flex;align-items:center;gap:8px;padding:11px 18px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;">
                <i class="fas fa-plus"></i> Add Item
            </a>
        </div>
    </div>

    @if(session('success')) <div style="background:#dcfce7;color:#15803d;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div> @endif
    @if(session('warning')) <div style="background:#fef3c7;color:#92400e;padding:12px 16px;border-radius:12px;margin-bottom:16px;border:1px solid #fde68a;"><i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}</div> @endif
    @if(session('error'))   <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('error') }}</div> @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
        <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border-left:4px solid #f97316;">
            <div style="font-size:13px;color:#64748b;font-weight:600;margin-bottom:6px;">TODAY'S ORDERS</div>
            <div style="font-size:30px;font-weight:800;color:#1e293b;">{{ $todayOrders }}</div>
        </div>
        <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border-left:4px solid #f59e0b;">
            <div style="font-size:13px;color:#64748b;font-weight:600;margin-bottom:6px;">PENDING / IN PROGRESS</div>
            <div style="font-size:30px;font-weight:800;color:#f59e0b;">{{ $pendingCount }}</div>
            @if($pendingCount > 0)
            <a href="{{ route('food-orders.index', ['status' => 'pending']) }}" style="font-size:12px;color:#f97316;font-weight:700;text-decoration:none;">View pending →</a>
            @endif
        </div>
        <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border-left:4px solid #16a34a;">
            <div style="font-size:13px;color:#64748b;font-weight:600;margin-bottom:6px;">TODAY'S REVENUE</div>
            <div style="font-size:30px;font-weight:800;color:#16a34a;">₹ {{ number_format((float)$todayRevenue, 2) }}</div>
        </div>
        <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border-left:4px solid #3b82f6;">
            <div style="font-size:13px;color:#64748b;font-weight:600;margin-bottom:6px;">MENU ITEMS</div>
            <div style="font-size:30px;font-weight:800;color:#1e293b;">{{ $totalItems }}</div>
            <div style="font-size:12px;color:#94a3b8;">in {{ $totalCategories }} categories</div>
        </div>
    </div>

    <div style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin:0;">Recent Orders</h2>
            <a href="{{ route('food-orders.index') }}" style="font-size:13px;color:#f97316;font-weight:700;text-decoration:none;">View all →</a>
        </div>

        @if($recentOrders->isEmpty())
        <div style="padding:40px;text-align:center;color:#94a3b8;">
            <i class="fas fa-receipt" style="font-size:36px;margin-bottom:10px;"></i>
            <p style="font-size:14px;">No orders yet. Print QR codes for your rooms and start receiving orders.</p>
        </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:700;">Order #</th>
                        <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:700;">Room</th>
                        <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:700;">Guest</th>
                        <th style="padding:10px 12px;text-align:center;color:#64748b;font-weight:700;">Items</th>
                        <th style="padding:10px 12px;text-align:right;color:#64748b;font-weight:700;">Total</th>
                        <th style="padding:10px 12px;text-align:center;color:#64748b;font-weight:700;">Status</th>
                        <th style="padding:10px 12px;text-align:center;color:#64748b;font-weight:700;">Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $o)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px;font-weight:700;color:#1e293b;">{{ $o->order_number }}</td>
                        <td style="padding:12px;"><span style="background:#fef3c7;color:#92400e;padding:3px 9px;border-radius:8px;font-weight:700;font-size:12px;">{{ $o->room_number }}</span></td>
                        <td style="padding:12px;color:#475569;">{{ $o->guest_name ?: '—' }}</td>
                        <td style="padding:12px;text-align:center;color:#64748b;">{{ $o->items->sum('quantity') }}</td>
                        <td style="padding:12px;text-align:right;font-weight:700;">₹ {{ number_format((float)$o->total_amount, 2) }}</td>
                        <td style="padding:12px;text-align:center;"><span style="background:{{ $o->statusColor() }}22;color:{{ $o->statusColor() }};padding:3px 10px;border-radius:8px;font-size:11px;font-weight:700;">{{ $o->statusLabel() }}</span></td>
                        <td style="padding:12px;text-align:center;color:#94a3b8;font-size:12px;">{{ $o->created_at->diffForHumans() }}</td>
                        <td style="padding:12px;text-align:right;"><a href="{{ route('food-orders.show', $o->id) }}" style="color:#f97316;text-decoration:none;font-weight:700;font-size:13px;">View →</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ══ MENU ITEMS MANAGEMENT ═══════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
            <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-list" style="color:#f97316;"></i> Menu Items ({{ $menuItems->count() }})</h2>
            <a href="{{ route('food-menu.items.create') }}" style="font-size:13px;color:#fff;background:linear-gradient(135deg,#f97316,#ea580c);padding:8px 14px;border-radius:10px;font-weight:700;text-decoration:none;"><i class="fas fa-plus"></i> Add Item</a>
        </div>
        @if($menuItems->isEmpty())
        <div style="padding:40px;text-align:center;color:#94a3b8;">
            <i class="fas fa-utensils" style="font-size:36px;margin-bottom:10px;"></i>
            <p style="font-size:14px;margin:0 0 12px;">No menu items yet. Add categories first, then create items.</p>
            <a href="{{ route('food-menu.categories') }}" style="color:#f97316;font-weight:700;text-decoration:none;">Manage categories →</a>
        </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:700;">Item</th>
                        <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:700;">Category</th>
                        <th style="padding:10px 12px;text-align:right;color:#64748b;font-weight:700;">Price</th>
                        <th style="padding:10px 12px;text-align:center;color:#64748b;font-weight:700;">Status</th>
                        <th style="padding:10px 12px;text-align:right;color:#64748b;font-weight:700;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($menuItems as $item)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                @if($item->image)
                                <img src="{{ $item->imageUrl() }}" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
                                @else
                                <div style="width:40px;height:40px;background:#fef3c7;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-utensils" style="color:#f59e0b;"></i></div>
                                @endif
                                <div>
                                    <div style="font-weight:700;color:#1e293b;">{{ $item->name }}</div>
                                    @if($item->description)<div style="font-size:11px;color:#94a3b8;">{{ \Illuminate\Support\Str::limit($item->description, 50) }}</div>@endif
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px;color:#475569;">{{ $item->category?->name ?? '—' }}</td>
                        <td style="padding:12px;text-align:right;font-weight:700;color:#1e293b;">₹ {{ number_format((float)$item->price, 2) }}</td>
                        <td style="padding:12px;text-align:center;">
                            <span style="padding:3px 10px;border-radius:8px;font-size:11px;font-weight:700;background:{{ $item->is_available ? '#dcfce7' : '#fee2e2' }};color:{{ $item->is_available ? '#15803d' : '#b91c1c' }};">
                                {{ $item->is_available ? 'Available' : 'Hidden' }}
                            </span>
                        </td>
                        <td style="padding:12px;text-align:right;white-space:nowrap;">
                            <form action="{{ route('food-menu.items.toggle', $item->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" style="background:none;border:none;color:#64748b;font-size:13px;font-weight:700;cursor:pointer;padding:4px 8px;">{{ $item->is_available ? 'Hide' : 'Show' }}</button>
                            </form>
                            <a href="{{ route('food-menu.items.edit', $item->id) }}" style="color:#f97316;text-decoration:none;font-weight:700;font-size:13px;padding:4px 8px;">Edit</a>
                            <form action="{{ route('food-menu.items.destroy', $item->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete {{ addslashes($item->name) }}?');">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:#dc2626;font-size:13px;font-weight:700;cursor:pointer;padding:4px 8px;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
