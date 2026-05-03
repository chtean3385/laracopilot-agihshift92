@extends('layouts.admin')
@section('title', 'Order ' . $order->order_number)

@section('content')
<div style="padding:24px;max-width:1100px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h1 style="font-size:24px;font-weight:800;color:#1e293b;margin:0;display:flex;align-items:center;gap:12px;">
                Order {{ $order->order_number }}
                <span style="background:{{ $order->statusColor() }}22;color:{{ $order->statusColor() }};padding:4px 12px;border-radius:10px;font-size:13px;font-weight:700;">{{ $order->statusLabel() }}</span>
            </h1>
            <p style="color:#64748b;margin:6px 0 0 0;font-size:13px;">Placed {{ $order->created_at->format('d M Y, h:i A') }} ({{ $order->created_at->diffForHumans() }})</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('food-orders.kot', $order->id) }}" target="_blank" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-print"></i> KOT Print</a>
            <a href="{{ route('food-orders.index') }}" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    @if(session('success')) <div style="background:#dcfce7;color:#15803d;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div> @endif
    @if(session('error'))   <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('error') }}</div> @endif
    @if($errors->any())     <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{!! implode('<br>', $errors->all()) !!}</div> @endif

    <div style="display:grid;grid-template-columns:1fr 320px;gap:18px;">
        <div>
            <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);margin-bottom:18px;">
                <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0 0 14px 0;">Items</h2>
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    <thead><tr style="background:#f8fafc;">
                        <th style="padding:9px 10px;text-align:left;color:#64748b;font-weight:700;">Item</th>
                        <th style="padding:9px 10px;text-align:right;color:#64748b;font-weight:700;">Price</th>
                        <th style="padding:9px 10px;text-align:center;color:#64748b;font-weight:700;width:140px;">Qty</th>
                        <th style="padding:9px 10px;text-align:right;color:#64748b;font-weight:700;">Total</th>
                    </tr></thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:11px 10px;font-weight:600;color:#1e293b;">{{ $item->name }}</td>
                            <td style="padding:11px 10px;text-align:right;color:#475569;">₹ {{ number_format((float)$item->price, 2) }}</td>
                            <td style="padding:11px 10px;text-align:center;">
                                @if(in_array($order->status, ['pending','in_progress']))
                                <form method="POST" action="{{ route('food-orders.edit-item', $order->id) }}" style="display:inline-flex;gap:6px;align-items:center;justify-content:center;">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="0" max="99" style="width:60px;padding:6px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px;text-align:center;">
                                    <button type="submit" style="padding:6px 9px;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">Save</button>
                                </form>
                                @else
                                <span style="font-weight:700;">{{ $item->quantity }}</span>
                                @endif
                            </td>
                            <td style="padding:11px 10px;text-align:right;font-weight:700;color:#1e293b;">₹ {{ number_format((float)$item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #e2e8f0;background:#f8fafc;">
                            <td colspan="3" style="padding:14px 10px;text-align:right;font-weight:800;color:#1e293b;font-size:15px;">TOTAL</td>
                            <td style="padding:14px 10px;text-align:right;font-weight:800;color:#f97316;font-size:18px;">₹ {{ number_format((float)$order->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                @if(in_array($order->status, ['pending','in_progress']))
                <div style="margin-top:16px;padding-top:16px;border-top:1px dashed #e2e8f0;">
                    <div style="font-size:13px;font-weight:700;color:#64748b;margin-bottom:8px;">Add another item</div>
                    <form method="POST" action="{{ route('food-orders.add-item', $order->id) }}" style="display:flex;gap:8px;">
                        @csrf
                        <select name="food_item_id" required style="flex:1;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;">
                            <option value="">-- pick a menu item --</option>
                            @foreach(\App\Models\FoodItem::where('is_available', true)->orderBy('name')->get() as $fi)
                            <option value="{{ $fi->id }}">{{ $fi->name }} — ₹ {{ number_format((float)$fi->price, 2) }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="quantity" min="1" max="99" value="1" style="width:80px;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;text-align:center;">
                        <button type="submit" style="padding:9px 16px;background:#f97316;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;">Add</button>
                    </form>
                </div>
                @endif
            </div>

            @if($order->guest_notes)
            <div style="background:#fef3c7;border-radius:14px;padding:16px 20px;margin-bottom:18px;">
                <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:6px;"><i class="fas fa-sticky-note"></i> GUEST NOTES</div>
                <div style="color:#1e293b;font-size:14px;line-height:1.5;">{{ $order->guest_notes }}</div>
            </div>
            @endif

            @if($order->cancellation_reason)
            <div style="background:#fee2e2;border-radius:14px;padding:16px 20px;margin-bottom:18px;">
                <div style="font-size:12px;font-weight:700;color:#b91c1c;margin-bottom:6px;">CANCELLATION REASON</div>
                <div style="color:#1e293b;font-size:14px;">{{ $order->cancellation_reason }}</div>
            </div>
            @endif
        </div>

        <div>
            <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,.05);margin-bottom:14px;">
                <h3 style="font-size:14px;font-weight:800;color:#64748b;text-transform:uppercase;margin:0 0 12px 0;">Guest &amp; Room</h3>
                <div style="font-size:13px;color:#64748b;">Room</div>
                <div style="font-size:24px;font-weight:800;color:#1e293b;margin-bottom:12px;">{{ $order->room_number }}</div>

                <div style="font-size:13px;color:#64748b;">Guest Name</div>
                <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:10px;">{{ $order->guest_name ?: '—' }}</div>

                <div style="font-size:13px;color:#64748b;">Phone</div>
                <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:10px;">
                    @if($order->guest_phone)
                    <a href="tel:{{ $order->guest_phone }}" style="color:#16a34a;text-decoration:none;"><i class="fas fa-phone"></i> {{ $order->guest_phone }}</a>
                    @else — @endif
                </div>

                @if($linkedBooking)
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;">
                    <div style="font-size:12px;color:#16a34a;font-weight:700;margin-bottom:4px;"><i class="fas fa-link"></i> LINKED BOOKING</div>
                    <a href="{{ route('bookings.show', $linkedBooking->id) }}" style="font-size:13px;color:#1e293b;font-weight:700;text-decoration:none;">#{{ $linkedBooking->id }} — {{ optional($linkedBooking->customer)->name ?? 'Guest' }}</a>
                    @if(optional($linkedBooking->customer)->phone)
                    <div style="font-size:12px;color:#64748b;margin-top:3px;">Booking phone: {{ $linkedBooking->customer->phone }}</div>
                    @endif
                </div>
                @else
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;">
                    <div style="font-size:12px;color:#b91c1c;font-weight:700;"><i class="fas fa-exclamation-triangle"></i> No checked-in booking found for room {{ $order->room_number }}.</div>
                    <div style="font-size:11px;color:#64748b;margin-top:4px;">Approving will not bill the room — order will be marked complete only.</div>
                </div>
                @endif
            </div>

            @if(in_array($order->status, ['pending','in_progress']))
            <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,.05);">
                <h3 style="font-size:14px;font-weight:800;color:#64748b;text-transform:uppercase;margin:0 0 12px 0;">Actions</h3>

                @if($order->status === 'pending')
                <form method="POST" action="{{ route('food-orders.status', $order->id) }}" style="margin-bottom:8px;">
                    @csrf
                    <input type="hidden" name="status" value="in_progress">
                    <button type="submit" style="width:100%;padding:11px;background:#dbeafe;color:#1d4ed8;border:1.5px solid #bfdbfe;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;"><i class="fas fa-clock"></i> Mark In Progress</button>
                </form>
                @endif

                <form method="POST" action="{{ route('food-orders.status', $order->id) }}" style="margin-bottom:8px;" onsubmit="return confirm('Approve this order? Charges will be added to the guest room bill.');">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" style="width:100%;padding:12px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;border-radius:10px;font-weight:800;cursor:pointer;font-size:14px;"><i class="fas fa-check"></i> Approve &amp; Bill to Room</button>
                </form>

                <form method="POST" action="{{ route('food-orders.status', $order->id) }}" id="cancelForm" onsubmit="return confirm('Cancel this order?');">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">
                    <input type="text" name="cancellation_reason" placeholder="Reason (optional)" maxlength="500" style="width:100%;padding:9px;border:1.5px solid #fca5a5;border-radius:8px;font-size:12px;margin-bottom:6px;">
                    <button type="submit" style="width:100%;padding:11px;background:#fee2e2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;"><i class="fas fa-times"></i> Cancel Order</button>
                </form>
            </div>
            @elseif($order->status === 'approved' && $order->approvedBy)
            <div style="background:#f0fdf4;border-radius:14px;padding:16px;border:1px solid #86efac;">
                <div style="font-size:12px;color:#15803d;font-weight:700;text-transform:uppercase;margin-bottom:4px;"><i class="fas fa-check-circle"></i> Approved</div>
                <div style="font-size:13px;color:#1e293b;">By <strong>{{ $order->approvedBy->name }}</strong></div>
                <div style="font-size:12px;color:#64748b;">{{ optional($order->approved_at)->format('d M Y, h:i A') }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
