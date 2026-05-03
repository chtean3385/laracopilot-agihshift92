<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>{{ $hotel->name }} — Restaurant Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fafafa; color: #1e293b; padding-bottom: 110px; }
        header { background: linear-gradient(135deg,#dc2626,#991b1b); color: #fff; padding: 22px 18px 18px; position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
        h1 { font-size: 22px; font-weight: 800; }
        .badge { background: rgba(255,255,255,.22); padding: 4px 12px; border-radius: 14px; font-size: 13px; font-weight: 700; display: inline-block; margin-top: 6px; margin-right: 6px; }
        .nav-pills { display: flex; gap: 8px; padding: 12px 14px; overflow-x: auto; background: #fff; border-bottom: 1px solid #f1f5f9; position: sticky; top: 84px; z-index: 9; }
        .nav-pills a { white-space: nowrap; padding: 7px 14px; background: #f1f5f9; color: #475569; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: 700; }
        .nav-pills a.active { background: #dc2626; color: #fff; }
        .category { padding: 16px; }
        .cat-title { font-size: 18px; font-weight: 800; margin-bottom: 12px; padding-left: 4px; color: #1e293b; }
        .item-card { background: #fff; border-radius: 16px; padding: 14px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.05); display: flex; gap: 14px; }
        .item-icon { width: 70px; height: 70px; border-radius: 12px; background: #fef2f2; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 26px; }
        .item-body { flex: 1; min-width: 0; }
        .item-name { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .item-desc { font-size: 12px; color: #64748b; line-height: 1.4; margin-bottom: 8px; }
        .item-row { display: flex; justify-content: space-between; align-items: center; }
        .item-price { font-size: 16px; font-weight: 800; color: #dc2626; }
        .qty-control { display: flex; align-items: center; gap: 6px; }
        .qty-btn { width: 30px; height: 30px; border-radius: 50%; border: none; background: #fee2e2; color: #dc2626; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .qty-btn.add { background: #dc2626; color: #fff; }
        .qty-display { min-width: 22px; text-align: center; font-weight: 800; font-size: 15px; }
        .cart-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; padding: 14px 16px; box-shadow: 0 -4px 18px rgba(0,0,0,.12); display: flex; align-items: center; justify-content: space-between; gap: 12px; z-index: 20; }
        .cart-info { font-size: 13px; color: #64748b; }
        .cart-total { font-size: 20px; font-weight: 800; color: #1e293b; }
        .cart-btn { padding: 13px 22px; background: linear-gradient(135deg,#dc2626,#991b1b); color: #fff; border: none; border-radius: 26px; font-size: 14px; font-weight: 800; cursor: pointer; }
        .cart-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 30; align-items: flex-end; }
        .modal-bg.show { display: flex; }
        .modal { background: #fff; width: 100%; max-width: 540px; margin: auto auto 0; border-radius: 22px 22px 0 0; padding: 22px 18px; max-height: 92vh; overflow-y: auto; }
        .modal h2 { font-size: 19px; font-weight: 800; margin-bottom: 14px; }
        .field { margin-bottom: 12px; }
        .field label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; }
        .field input, .field textarea, .field select { width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; font-family: inherit; }
        .summary-list { background: #f8fafc; border-radius: 12px; padding: 12px; margin-bottom: 14px; }
        .summary-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; }
        .summary-total { display: flex; justify-content: space-between; padding-top: 10px; margin-top: 8px; border-top: 1.5px dashed #cbd5e1; font-weight: 800; font-size: 16px; }
        .submit-btn { width: 100%; padding: 14px; background: linear-gradient(135deg,#dc2626,#991b1b); color: #fff; border: none; border-radius: 14px; font-size: 15px; font-weight: 800; cursor: pointer; margin-top: 6px; }
        .close-btn { background: transparent; border: none; font-size: 22px; color: #64748b; cursor: pointer; float: right; }
        .empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .veg-dot { display:inline-block; width:10px; height:10px; border-radius:50%; vertical-align:middle; margin-right:4px; }
    </style>
</head>
@php
    $tableName = $table instanceof \App\Models\RestaurantTable ? $table->name : ($table ?: null);
    $mode = $roomNumber ? 'room' : ($tableName ? 'table' : 'direct');
@endphp
<body>
    <header>
        <h1><i class="fas fa-utensils"></i> {{ $hotel->name }}</h1>
        <div style="font-size:12px;opacity:.92;margin-top:3px;">Restaurant Menu</div>
        @if($roomNumber)
            <div class="badge"><i class="fas fa-door-open"></i> Room {{ $roomNumber }}</div>
        @elseif($tableName)
            <div class="badge"><i class="fas fa-chair"></i> {{ $tableName }}</div>
        @endif
    </header>

    @if($categories->isEmpty())
    <div class="empty">
        <i class="fas fa-utensils" style="font-size:42px;margin-bottom:14px;"></i>
        <p>The menu is currently empty. Please check again later.</p>
    </div>
    @else
    <nav class="nav-pills" id="navPills">
        @foreach($categories as $i => $cat)
        @if($itemsByCat->has($cat->id) && $itemsByCat[$cat->id]->isNotEmpty())
        <a href="#cat-{{ $cat->id }}" class="{{ $i===0?'active':'' }}">{{ $cat->name }}</a>
        @endif
        @endforeach
    </nav>

    @foreach($categories as $cat)
    @if($itemsByCat->has($cat->id) && $itemsByCat[$cat->id]->isNotEmpty())
    <section id="cat-{{ $cat->id }}" class="category">
        <h2 class="cat-title">{{ $cat->name }}</h2>
        @foreach($itemsByCat[$cat->id] as $item)
        @php
            $dot = match($item->food_type) {
                'veg' => '#16a34a',
                'nonveg' => '#dc2626',
                'beverage' => '#0ea5e9',
                default => '#94a3b8',
            };
        @endphp
        <div class="item-card">
            @if($item->imageUrl())
            <div class="item-icon" style="background:#fff;padding:0;overflow:hidden;"><img src="{{ $item->imageUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover;"></div>
            @else
            <div class="item-icon"><i class="fas fa-utensils"></i></div>
            @endif
            <div class="item-body">
                <div class="item-name"><span class="veg-dot" style="background:{{ $dot }};"></span>{{ $item->name }}</div>
                @if($item->description)<div class="item-desc">{{ $item->description }}</div>@endif
                <div class="item-row">
                    <div class="item-price">₹ {{ number_format((float)$item->price, 2) }}</div>
                    <div class="qty-control" data-item-id="{{ $item->id }}" data-name="{{ $item->name }}" data-price="{{ $item->price }}">
                        <button type="button" class="qty-btn dec" style="display:none;">−</button>
                        <span class="qty-display" style="display:none;">0</span>
                        <button type="button" class="qty-btn add">+</button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </section>
    @endif
    @endforeach
    @endif

    <div class="cart-bar">
        <div>
            <div class="cart-info"><span id="cartCount">0</span> item(s)</div>
            <div class="cart-total">₹ <span id="cartTotal">0.00</span></div>
        </div>
        <button class="cart-btn" id="checkoutBtn" disabled onclick="openCheckout()"><i class="fas fa-shopping-cart"></i> Order</button>
    </div>

    <div class="modal-bg" id="modal">
        <div class="modal">
            <button class="close-btn" onclick="closeCheckout()">×</button>
            <h2>Confirm your order</h2>
            <div class="summary-list" id="summaryList"></div>
            <div class="summary-total"><span>Total</span><span>₹ <span id="modalTotal">0.00</span></span></div>

            <form id="orderForm" method="POST" action="{{ route('public.restaurant.order', $hotel->slug) }}" style="margin-top:16px;">
                @csrf
                <input type="hidden" name="_menu_token" value="{{ $token }}">
                <input type="hidden" name="_lock_mode"  value="{{ $lockMode ?? 'open' }}">
                <input type="hidden" name="_lock_value" value="{{ $lockValue ?? '' }}">

                <div class="field">
                    <label>Where are you ordering from?</label>
                    <select name="mode" id="modeSelect" onchange="onModeChange()">
                        <option value="room"   {{ $mode==='room'   ? 'selected' : '' }}>🛏️ My hotel room</option>
                        <option value="table"  {{ $mode==='table'  ? 'selected' : '' }}>🪑 Restaurant table</option>
                        <option value="direct" {{ $mode==='direct' ? 'selected' : '' }}>🚶 Just walk-in</option>
                    </select>
                </div>

                <div class="field" id="roomField" style="{{ $mode==='room' ? '' : 'display:none;' }}">
                    <label>Room Number *</label>
                    <input type="text" name="room_number" value="{{ $roomNumber }}" maxlength="20" {{ $roomNumber ? 'readonly' : '' }} style="{{ $roomNumber ? 'background:#f1f5f9;' : '' }}">
                </div>

                <div class="field" id="tableField" style="{{ $mode==='table' ? '' : 'display:none;' }}">
                    <label>Table *</label>
                    <input type="text" name="table_name" value="{{ $tableName }}" maxlength="50" {{ $tableName ? 'readonly' : '' }} style="{{ $tableName ? 'background:#f1f5f9;' : '' }}">
                </div>

                <div class="field">
                    <label>Your Name (so we can confirm)</label>
                    <input type="text" name="guest_name" maxlength="100" placeholder="Optional">
                </div>
                <div class="field">
                    <label>Phone (we may call to confirm)</label>
                    <input type="tel" name="guest_phone" maxlength="30" placeholder="Optional">
                </div>
                <div class="field">
                    <label>Notes (allergies, instructions)</label>
                    <textarea name="guest_notes" maxlength="500" rows="2" placeholder="e.g. less spice, no onions"></textarea>
                </div>
                <div id="hiddenItems"></div>
                <button type="submit" class="submit-btn"><i class="fas fa-check"></i> Place Order</button>
            </form>
        </div>
    </div>

<script>
const cart = {};
function fmt(n) { return n.toFixed(2); }
function refreshCart() {
    let count = 0, total = 0;
    Object.values(cart).forEach(c => { count += c.qty; total += c.qty * c.price; });
    document.getElementById('cartCount').textContent = count;
    document.getElementById('cartTotal').textContent = fmt(total);
    document.getElementById('checkoutBtn').disabled = count === 0;
}
document.querySelectorAll('.qty-control').forEach(ctrl => {
    const id = ctrl.dataset.itemId;
    const name = ctrl.dataset.name;
    const price = parseFloat(ctrl.dataset.price);
    const dec = ctrl.querySelector('.dec');
    const display = ctrl.querySelector('.qty-display');
    function render() {
        const qty = cart[id]?.qty || 0;
        if (qty > 0) {
            dec.style.display = 'flex';
            display.style.display = 'inline-block';
            display.textContent = qty;
        } else {
            dec.style.display = 'none';
            display.style.display = 'none';
        }
    }
    ctrl.querySelector('.add').addEventListener('click', () => {
        if (!cart[id]) cart[id] = { qty: 0, price, name };
        cart[id].qty += 1;
        render(); refreshCart();
    });
    dec.addEventListener('click', () => {
        if (!cart[id]) return;
        cart[id].qty -= 1;
        if (cart[id].qty <= 0) delete cart[id];
        render(); refreshCart();
    });
});
function onModeChange() {
    const mode = document.getElementById('modeSelect').value;
    document.getElementById('roomField').style.display  = mode === 'room'  ? '' : 'none';
    document.getElementById('tableField').style.display = mode === 'table' ? '' : 'none';
}
function openCheckout() {
    const list = document.getElementById('summaryList');
    const hidden = document.getElementById('hiddenItems');
    list.innerHTML = ''; hidden.innerHTML = '';
    let total = 0, idx = 0;
    Object.entries(cart).forEach(([id, c]) => {
        const lt = c.qty * c.price; total += lt;
        const row = document.createElement('div'); row.className = 'summary-row';
        row.innerHTML = '<span>' + c.name + ' × ' + c.qty + '</span><span>₹ ' + fmt(lt) + '</span>';
        list.appendChild(row);
        const i1 = document.createElement('input'); i1.type='hidden'; i1.name='items['+idx+'][id]';  i1.value=id;     hidden.appendChild(i1);
        const i2 = document.createElement('input'); i2.type='hidden'; i2.name='items['+idx+'][qty]'; i2.value=c.qty;  hidden.appendChild(i2);
        idx++;
    });
    document.getElementById('modalTotal').textContent = fmt(total);
    document.getElementById('modal').classList.add('show');
}
function closeCheckout() { document.getElementById('modal').classList.remove('show'); }
document.querySelectorAll('#navPills a').forEach(a => {
    a.addEventListener('click', () => {
        document.querySelectorAll('#navPills a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
    });
});
</script>
</body>
</html>
