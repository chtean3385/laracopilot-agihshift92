{{-- Order items table + totals — re-rendered via AJAX --}}
<h3 class="font-bold text-gray-800 mb-4" style="margin-top:0;">🛒 Order Items
    <span class="text-sm font-normal text-gray-500">({{ $order->items->count() }} items)</span>
</h3>

@if($order->items->isEmpty())
<p class="text-gray-400 text-sm text-center py-8">No items added yet. Click menu items above to add.</p>
@else
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-2 text-gray-600">Item</th>
                <th class="text-center py-2 text-gray-600">Qty</th>
                <th class="text-right py-2 text-gray-600">Price</th>
                <th class="text-right py-2 text-gray-600">Total</th>
                @if($order->isOpen())
                <th class="py-2"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr class="border-b border-gray-100" id="item-row-{{ $item->id }}">
                <td class="py-2">
                    <div class="font-medium">
                        {{ $item->food_type === 'veg' ? '🟢' : ($item->food_type === 'nonveg' ? '🔴' : '🔵') }}
                        {{ $item->item_name }}
                    </div>
                    @if($item->kot_note)
                    <div class="text-xs text-gray-400">{{ $item->kot_note }}</div>
                    @endif
                </td>
                <td class="py-2 text-center">
                    @if($order->isOpen())
                    <span style="display:inline-flex;gap:4px;align-items:center;">
                        <input type="number" value="{{ $item->quantity }}" min="1" max="99"
                            onchange="updateQty({{ $item->id }}, this.value)"
                            style="width:54px;padding:3px 6px;border:1px solid #cbd5e1;border-radius:6px;text-align:center;font-size:12px;">
                    </span>
                    @else
                    {{ $item->quantity }}
                    @endif
                </td>
                <td class="py-2 text-right">₹{{ number_format($item->final_price, 2) }}</td>
                <td class="py-2 text-right font-medium">₹{{ number_format($item->subtotal, 2) }}</td>
                @if($order->isOpen())
                <td class="py-2 text-center">
                    <button type="button" onclick="removeItem({{ $item->id }})" class="text-red-400 hover:text-red-600 text-xs" style="background:none;border:none;cursor:pointer;color:#f87171;">✕</button>
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="mt-4 border-t border-gray-200 pt-4 space-y-1 text-sm">
    <div class="flex justify-between text-gray-600">
        <span>Subtotal</span>
        <span>₹{{ number_format($order->subtotal, 2) }}</span>
    </div>
    <div class="flex justify-between text-gray-600">
        <span>GST ({{ $order->tax_rate }}%)</span>
        <span>₹{{ number_format($order->tax_amount, 2) }}</span>
    </div>
    <div class="flex justify-between font-bold text-gray-800 text-base border-t border-gray-200 pt-2 mt-2">
        <span>Total</span>
        <span>₹{{ number_format($order->total, 2) }}</span>
    </div>
</div>
@endif
