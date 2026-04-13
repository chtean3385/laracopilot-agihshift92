<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="widget-token" content="{{ $widgetToken }}">
    <title>Book Your Stay</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #fff;
            padding: 20px 18px 30px;
            font-size: 14px;
            color: #111827;
        }

        .header {
            text-align: center; padding: 12px 0 18px;
            border-bottom: 1px solid #f0f0f0; margin-bottom: 18px;
        }
        .header h1 { font-size: 1.2rem; font-weight: 800; color: #111827; }
        .header p { font-size: .8rem; color: #9ca3af; margin-top: 2px; }

        .step-label { font-size: .75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; margin-top: 16px; }

        label { display: block; font-size: .77rem; font-weight: 600; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .03em; }
        input[type=text], input[type=email], input[type=date], input[type=number], select, textarea {
            width: 100%; border: 1.5px solid #e5e7eb; border-radius: 8px;
            padding: 9px 11px; font-size: .88rem; color: #111827;
            background: #fff; transition: border-color .2s;
            font-family: inherit; margin-bottom: 10px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: {{ $widgetSettings->primary_color }};
            box-shadow: 0 0 0 3px {{ $widgetSettings->primary_color }}22;
        }
        .phone-row {
            display: flex;
            align-items: stretch;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            margin-bottom: 10px;
        }
        .phone-row:focus-within {
            border-color: {{ $widgetSettings->primary_color }};
            box-shadow: 0 0 0 3px {{ $widgetSettings->primary_color }}22;
        }
        .phone-row select {
            width: 158px;
            border: 0;
            border-right: 1px solid #e5e7eb;
            border-radius: 0;
            margin: 0;
            background: #f9fafb;
            flex-shrink: 0;
        }
        .phone-row input {
            border: 0;
            border-radius: 0;
            margin: 0;
            flex: 1;
            min-width: 0;
        }
        @media (max-width: 520px) {
            .phone-row { flex-direction: column; }
            .phone-row select {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid #e5e7eb;
            }
        }

        .date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .guest-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

        .rooms-placeholder { background: #f9fafb; border-radius: 10px; padding: 20px 14px; text-align: center; color: #9ca3af; font-size: .82rem; }
        .rooms-loading { text-align: center; color: {{ $widgetSettings->primary_color }}; padding: 16px; font-weight: 600; font-size: .88rem; }

        .room-card { border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 12px; margin-bottom: 8px; cursor: pointer; transition: all .15s; }
        .room-card:hover { border-color: {{ $widgetSettings->primary_color }}; }
        .room-card.selected { border-color: {{ $widgetSettings->primary_color }}; background: {{ $widgetSettings->primary_color }}0a; }
        .room-row { display: flex; justify-content: space-between; align-items: center; }
        .room-name { font-weight: 700; font-size: .88rem; }
        .room-price { font-weight: 800; color: {{ $widgetSettings->primary_color }}; font-size: 1rem; }
        .room-price small { font-size: .68rem; font-weight: 400; color: #9ca3af; }
        .room-sub { font-size: .72rem; color: #6b7280; margin-top: 3px; }
        .check-circle { display: none; width: 18px; height: 18px; background: {{ $widgetSettings->primary_color }}; border-radius: 50%; flex-shrink: 0; align-items: center; justify-content: center; }
        .room-card.selected .check-circle { display: flex; }
        .check-circle svg { width: 10px; fill: none; stroke: #fff; stroke-width: 2.5; stroke-linecap: round; }
        .room-card.unavailable { background: #fafafa; border-color: #f0f0f0; }
        .room-card.unavailable .room-name { color: #9ca3af; }
        .room-card.unavailable .room-price { color: #d1d5db; }

        .btn { width: 100%; padding: 14px; background: {{ $widgetSettings->primary_color }}; color: #fff; font-weight: 700; font-size: .95rem; border: none; border-radius: 10px; cursor: pointer; margin-top: 8px; font-family: inherit; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn:hover:not(:disabled) { opacity: .88; }

        .err { background: #fef2f2; color: #b91c1c; border-radius: 8px; padding: 8px 12px; font-size: .82rem; margin-bottom: 10px; display: none; }

        .divider { border: none; border-top: 1px solid #f0f0f0; margin: 16px 0; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $widgetSettings->widget_title }}</h1>
    <p>{{ $hotelSettings->resort_name ?? $hotel->name }}</p>
</div>

<form id="iframeForm" method="POST" action="{{ route('public.booking.store', $hotel->slug) }}">
    <input type="hidden" name="_widget_token" value="{{ $widgetToken }}">
    <input type="hidden" name="room_type" id="selectedRoomType">

    <div class="step-label">1 · Dates</div>
    <div class="date-row">
        <div>
            <label>Check-in</label>
            <input type="date" name="check_in" id="checkIn" required min="{{ now()->format('Y-m-d') }}">
        </div>
        <div>
            <label>Check-out</label>
            <input type="date" name="check_out" id="checkOut" required min="{{ now()->addDay()->format('Y-m-d') }}">
        </div>
    </div>

    <hr class="divider">
    <div class="step-label">2 · Room Type</div>
    <div id="roomsPlaceholder" class="rooms-placeholder">Select dates above to see rooms.</div>
    <div id="roomsLoading" class="rooms-loading" style="display:none;">Checking…</div>
    <div id="roomsList" style="display:none;"></div>
    <div id="noRooms" class="rooms-placeholder" style="display:none;">No rooms available. Try different dates.</div>

    <hr class="divider">
    <div class="step-label">3 · Your Details</div>
    <label>Full Name</label>
    <input type="text" name="guest_name" required placeholder="Your name">
    <label>Phone</label>
    <div class="phone-row">
        <select id="countryCode" name="country_code">
            <option value="91" selected>India (+91)</option>
            <option value="1">United States (+1)</option>
            <option value="44">United Kingdom (+44)</option>
            <option value="971">United Arab Emirates (+971)</option>
            <option value="65">Singapore (+65)</option>
            <option value="93">Afghanistan (+93)</option>
            <option value="355">Albania (+355)</option>
            <option value="213">Algeria (+213)</option>
            <option value="61">Australia (+61)</option>
            <option value="43">Austria (+43)</option>
            <option value="973">Bahrain (+973)</option>
            <option value="880">Bangladesh (+880)</option>
            <option value="32">Belgium (+32)</option>
            <option value="975">Bhutan (+975)</option>
            <option value="55">Brazil (+55)</option>
            <option value="855">Cambodia (+855)</option>
            <option value="1">Canada (+1)</option>
            <option value="86">China (+86)</option>
            <option value="57">Colombia (+57)</option>
            <option value="385">Croatia (+385)</option>
            <option value="420">Czech Republic (+420)</option>
            <option value="45">Denmark (+45)</option>
            <option value="20">Egypt (+20)</option>
            <option value="358">Finland (+358)</option>
            <option value="33">France (+33)</option>
            <option value="49">Germany (+49)</option>
            <option value="30">Greece (+30)</option>
            <option value="852">Hong Kong (+852)</option>
            <option value="36">Hungary (+36)</option>
            <option value="62">Indonesia (+62)</option>
            <option value="353">Ireland (+353)</option>
            <option value="39">Italy (+39)</option>
            <option value="81">Japan (+81)</option>
            <option value="962">Jordan (+962)</option>
            <option value="7">Kazakhstan (+7)</option>
            <option value="60">Malaysia (+60)</option>
            <option value="968">Oman (+968)</option>
            <option value="63">Philippines (+63)</option>
            <option value="974">Qatar (+974)</option>
            <option value="966">Saudi Arabia (+966)</option>
            <option value="27">South Africa (+27)</option>
            <option value="82">South Korea (+82)</option>
            <option value="34">Spain (+34)</option>
            <option value="94">Sri Lanka (+94)</option>
            <option value="46">Sweden (+46)</option>
            <option value="41">Switzerland (+41)</option>
            <option value="66">Thailand (+66)</option>
            <option value="90">Turkey (+90)</option>
            <option value="84">Vietnam (+84)</option>
        </select>
        <input type="tel" name="phone" id="phoneInput" required placeholder="9876543210">
    </div>
    <label>Email (optional)</label>
    <input type="email" name="email" placeholder="you@example.com">
    <div class="guest-row">
        <div><label>Adults</label><input type="number" name="adults" value="1" min="1" required></div>
        <div><label>Children</label><input type="number" name="children" value="0" min="0"></div>
    </div>
    <label>Special Requests (optional)</label>
    <textarea name="special_requests" rows="2" placeholder="Early check-in, preferences…"></textarea>

    <div class="err" id="formErr"></div>
    <button type="submit" class="btn" id="btnSubmit" disabled>{{ $widgetSettings->button_text }}</button>
</form>

<script>
document.getElementById('iframeForm').addEventListener('submit', function(e) {
    const phone = document.getElementById('phoneInput');
    const code = document.getElementById('countryCode').value.replace(/[^0-9]/g, '');
    const raw = phone.value.trim().replace(/[^0-9]/g, '');
    if (!raw) {
        e.preventDefault();
        showErr('Enter a valid phone number.');
        return;
    }
    phone.value = raw.startsWith(code) ? raw : code + raw;
});

function showErr(m) { const e = document.getElementById('formErr'); e.textContent = m; e.style.display = 'block'; }
function hideErr()  { document.getElementById('formErr').style.display = 'none'; }

function checkDates() {
    const ci = document.getElementById('checkIn').value;
    const co = document.getElementById('checkOut').value;
    if (!ci || !co || co <= ci) { showErr('Check-out must be after check-in.'); return; }
    hideErr();
    fetchRooms(ci, co);
}

document.getElementById('checkIn').addEventListener('change', function() {
    const co = document.getElementById('checkOut');
    if (!co.value || co.value <= this.value) {
        const next = new Date(this.value);
        next.setDate(next.getDate() + 1);
        co.value = next.toISOString().slice(0, 10);
        co.min   = next.toISOString().slice(0, 10);
    }
    checkDates();
});
document.getElementById('checkOut').addEventListener('change', checkDates);

function fetchRooms(ci, co) {
    document.getElementById('roomsPlaceholder').style.display = 'none';
    document.getElementById('roomsLoading').style.display     = 'block';
    document.getElementById('roomsList').style.display        = 'none';
    document.getElementById('noRooms').style.display          = 'none';
    document.getElementById('selectedRoomType').value         = '';
    document.getElementById('btnSubmit').disabled             = true;

    fetch("{{ route('public.booking.availability', $hotel->slug) }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Widget-Token': document.querySelector('meta[name="widget-token"]').content },
        body: JSON.stringify({ check_in: ci, check_out: co, _widget_token: document.querySelector('meta[name="widget-token"]').content })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('roomsLoading').style.display = 'none';
        const showPrices  = data.show_prices !== false;
        const showPhotos  = data.show_room_photos === true;
        renderRooms(data.types || [], data.nights, showPrices, showPhotos);
        document.getElementById('roomsList').style.display = 'block';
    })
    .catch(() => {
        document.getElementById('roomsLoading').style.display = 'none';
        document.getElementById('roomsPlaceholder').style.display = 'block';
    });
}

function renderRooms(types, nights, showPrices, showPhotos) {
    const list = document.getElementById('roomsList');
    list.innerHTML = '';

    if (!types || types.length === 0) {
        list.innerHTML = '<div class="rooms-placeholder">No rooms configured. Please contact the hotel directly.</div>';
        return;
    }

    showPhotos = !!showPhotos;

    types.forEach(t => {
        const card = document.createElement('div');
        card.className   = 'room-card' + (!t.available ? ' unavailable' : '');
        card.dataset.type = t.type;

        const priceHtml = showPrices
            ? `<div class="room-price">₹${t.price_per_night.toLocaleString('en-IN')}<small>/night</small></div>`
            : '';
        const totalHtml = showPrices && t.available
            ? `<div class="room-sub" style="margin-top:2px;color:#9ca3af;font-size:.7rem;">Total: ₹${t.total_price.toLocaleString('en-IN')}</div>`
            : '';
        const soldOutBadge = !t.available
            ? `<span style="font-size:.68rem;font-weight:700;background:#fef3c7;color:#92400e;border-radius:20px;padding:2px 8px;white-space:nowrap;">Request Waitlist</span>`
            : '';
        const amenitiesHtml = t.amenities
            ? `<div style="font-size:.7rem;color:#9ca3af;margin-top:4px;">✦ ${t.amenities}</div>`
            : '';
        const descHtml = t.description
            ? `<div style="font-size:.75rem;color:#6b7280;margin-top:3px;line-height:1.4;">${t.description}</div>`
            : '';
        const photoHtml = showPhotos && t.photo_url
            ? `<img src="${t.photo_url}" alt="${t.type}" style="width:100%;height:120px;object-fit:cover;border-radius:7px 7px 0 0;display:block;margin:-12px -12px 10px -12px;width:calc(100% + 24px);" loading="lazy" onerror="this.style.display='none'">`
            : '';

        card.innerHTML = `
            ${photoHtml}
            <div class="room-row">
                <div style="flex:1;min-width:0;">
                    <div class="room-name">${t.type.charAt(0).toUpperCase() + t.type.slice(1)}</div>
                    <div class="room-sub">Up to ${t.capacity} guests · ${nights} night${nights>1?'s':''}</div>
                    ${descHtml}
                    ${amenitiesHtml}
                    ${totalHtml}
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;margin-left:10px;">
                    ${priceHtml}
                    ${soldOutBadge}
                    <div class="check-circle"><svg viewBox="0 0 10 8"><polyline points="0,4 4,8 10,0"/></svg></div>
                </div>
            </div>
        `;
        list.appendChild(card);
    });
    // Delegated listener — fires regardless of which child element is clicked
    list.addEventListener('click', function(e) {
        const card = e.target.closest('.room-card');
        if (!card || card.classList.contains('unavailable')) return;
        document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        document.getElementById('selectedRoomType').value = card.dataset.type;
        document.getElementById('btnSubmit').disabled = false;
        hideErr();
    });
}
</script>
</body>
</html>
