<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="widget-token" content="{{ $widgetToken }}">
    <title>Book Your Stay – {{ $hotelSettings->resort_name ?? $hotel->name }}</title>
    <link rel="icon" href="{{ asset('img/logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8f9fb; min-height: 100vh; }

        .hero {
            background: linear-gradient(120deg, {{ $widgetSettings->primary_color }} 0%, {{ $widgetSettings->primary_color }}cc 100%);
            color: #fff;
            padding: 48px 24px 120px;
            text-align: center;
        }
        .hero h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -.02em; }
        .hero p { margin-top: 8px; opacity: .85; font-size: 1.1rem; }

        .card {
            max-width: 700px;
            margin: -80px auto 40px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0,0,0,.10);
            padding: 36px 32px;
        }

        .step { margin-bottom: 28px; }
        .step h2 { font-size: 1rem; font-weight: 700; color: #374151; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        .step h2 .badge { background: {{ $widgetSettings->primary_color }}22; color: {{ $widgetSettings->primary_color }}; border-radius: 50%; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 800; flex-shrink: 0; }

        .date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media(max-width: 480px) { .date-row { grid-template-columns: 1fr; } }

        .form-group { margin-bottom: 14px; }
        label { display: block; font-size: .82rem; font-weight: 600; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .03em; }
        input[type=text], input[type=email], input[type=date], input[type=number], select, textarea {
            width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
            padding: 11px 14px; font-size: .95rem; color: #111827;
            background: #fff; transition: border-color .2s, box-shadow .2s;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: {{ $widgetSettings->primary_color }};
            box-shadow: 0 0 0 3px {{ $widgetSettings->primary_color }}22;
        }
        .iti { width: 100%; }
        .iti input { padding-left: 54px !important; }

        .guest-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .room-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;
            margin-top: 6px;
        }
        .room-card {
            border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px 14px;
            cursor: pointer; transition: all .18s; position: relative; user-select: none;
        }
        .room-card:hover { border-color: {{ $widgetSettings->primary_color }}; box-shadow: 0 4px 16px {{ $widgetSettings->primary_color }}22; }
        .room-card.selected { border-color: {{ $widgetSettings->primary_color }}; background: {{ $widgetSettings->primary_color }}0a; }
        .room-card input[type=radio] { position: absolute; opacity: 0; }
        .room-name { font-weight: 700; font-size: .95rem; color: #111827; }
        .room-price { font-size: 1.3rem; font-weight: 800; color: {{ $widgetSettings->primary_color }}; margin: 6px 0; }
        .room-price small { font-size: .75rem; font-weight: 500; color: #9ca3af; }
        .room-meta { font-size: .78rem; color: #6b7280; }
        .room-total { font-size: .82rem; font-weight: 600; color: #374151; margin-top: 4px; }
        .check-icon { position: absolute; top: 10px; right: 10px; width: 20px; height: 20px; background: {{ $widgetSettings->primary_color }}; border-radius: 50%; display: none; align-items: center; justify-content: center; }
        .room-card.selected .check-icon { display: flex; }
        .check-icon svg { width: 12px; height: 12px; fill: #fff; }

        .availability-placeholder {
            background: #f3f4f6; border-radius: 12px; padding: 28px 20px;
            text-align: center; color: #9ca3af; font-size: .92rem;
        }

        .loading { text-align: center; padding: 24px; color: {{ $widgetSettings->primary_color }}; font-weight: 600; }

        .btn-submit {
            width: 100%; padding: 16px;
            background: {{ $widgetSettings->primary_color }};
            color: #fff; font-size: 1rem; font-weight: 700;
            border: none; border-radius: 12px; cursor: pointer;
            transition: opacity .2s, transform .15s;
            letter-spacing: .01em;
        }
        .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
        .btn-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        .powered { text-align: center; font-size: .75rem; color: #d1d5db; margin-bottom: 16px; }
        .err { background: #fef2f2; color: #b91c1c; border-radius: 8px; padding: 10px 14px; font-size: .88rem; margin-bottom: 14px; display: none; }
        .badge-avail { display: inline-block; background: #d1fae5; color: #065f46; font-size: .7rem; font-weight: 600; border-radius: 50px; padding: 2px 8px; margin-left: 6px; }
    </style>
</head>
<body>
<div class="hero">
    <h1>{{ $widgetSettings->widget_title }}</h1>
    <p>{{ $hotelSettings->resort_name ?? $hotel->name }}</p>
</div>

<div class="card">
    <form id="bookingForm" method="POST" action="{{ route('public.booking.store', $hotel->slug) }}">
        <input type="hidden" name="_widget_token" value="{{ $widgetToken }}">
        <input type="hidden" name="room_type" id="selectedRoomType">

        {{-- Step 1: Dates --}}
        <div class="step">
            <h2><span class="badge">1</span> Select Dates</h2>
            <div class="date-row">
                <div class="form-group">
                    <label>Check-in</label>
                    <input type="date" name="check_in" id="checkIn" required min="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>Check-out</label>
                    <input type="date" name="check_out" id="checkOut" required min="{{ now()->addDay()->format('Y-m-d') }}">
                </div>
            </div>
        </div>

        {{-- Step 2: Room Selection --}}
        <div class="step">
            <h2><span class="badge">2</span> Choose Room Type</h2>
            <div id="roomsPlaceholder" class="availability-placeholder">
                Select check-in and check-out dates above to see available rooms.
            </div>
            <div id="roomsLoading" class="loading" style="display:none;">Checking availability…</div>
            <div id="roomsGrid" class="room-grid" style="display:none;"></div>
            <div id="noRooms" style="display:none;" class="availability-placeholder">
                No rooms available for the selected dates. Please try different dates.
            </div>
        </div>

        {{-- Step 3: Guest Details --}}
        <div class="step">
            <h2><span class="badge">3</span> Guest Details</h2>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="guest_name" placeholder="Your full name" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <div class="phone-row" style="display:grid;grid-template-columns:140px 1fr;gap:10px;align-items:start;">
                    <select id="countryCode" style="margin-bottom:0;">
                        <option value="91" selected>India (+91)</option>
                        <option value="1">United States (+1)</option>
                        <option value="44">United Kingdom (+44)</option>
                        <option value="971">UAE (+971)</option>
                        <option value="65">Singapore (+65)</option>
                    </select>
                    <input type="tel" name="phone" id="phoneInput" required placeholder="9876543210" style="margin-bottom:0;">
                </div>
            </div>
            <div class="form-group">
                <label>Email <small style="font-weight:400;text-transform:none;">(optional)</small></label>
                <input type="email" name="email" placeholder="you@example.com">
            </div>
            <div class="guest-row">
                <div class="form-group">
                    <label>Adults</label>
                    <input type="number" name="adults" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label>Children</label>
                    <input type="number" name="children" value="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label>Special Requests <small style="font-weight:400;text-transform:none;">(optional)</small></label>
                <textarea name="special_requests" rows="2" placeholder="Early check-in, dietary preferences…"></textarea>
            </div>
        </div>

        <div class="err" id="formErr"></div>

        <button type="submit" class="btn-submit" id="btnSubmit" disabled>
            {{ $widgetSettings->button_text }}
        </button>
    </form>
</div>
<div class="powered">Powered by Resort CRM</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const phone = document.getElementById('phoneInput');
    const code = document.getElementById('countryCode').value.replace(/[^0-9]/g, '');
    const raw = phone.value.trim().replace(/[^0-9]/g, '');
    if (!raw) {
        e.preventDefault();
        showErr('Please enter a valid phone number.');
        return;
    }
    if (!raw.startsWith(code)) {
        phone.value = code + raw;
    } else {
        phone.value = raw;
    }
});

const color  = '{{ $widgetSettings->primary_color }}';
let   nights = 0;

function showErr(msg) {
    const el = document.getElementById('formErr');
    el.textContent = msg; el.style.display = 'block';
}
function hideErr() { document.getElementById('formErr').style.display = 'none'; }

function checkDates() {
    const ci = document.getElementById('checkIn').value;
    const co = document.getElementById('checkOut').value;
    if (!ci || !co) return;
    if (co <= ci) {
        showErr('Check-out must be after check-in.');
        return;
    }
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
    document.getElementById('roomsGrid').style.display        = 'none';
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
        if (!data.types || data.types.length === 0) {
            document.getElementById('noRooms').style.display = 'block';
            return;
        }
        nights = data.nights;
        renderRooms(data.types);
        document.getElementById('roomsGrid').style.display = 'grid';
    })
    .catch(() => {
        document.getElementById('roomsLoading').style.display = 'none';
        document.getElementById('roomsPlaceholder').style.display = 'block';
        showErr('Could not fetch room availability. Please try again.');
    });
}

function renderRooms(types) {
    const grid = document.getElementById('roomsGrid');
    grid.innerHTML = '';
    types.forEach(t => {
        const card = document.createElement('div');
        card.className = 'room-card';
        card.dataset.type = t.type;
        card.innerHTML = `
            <input type="radio" name="_room_type_radio" value="${t.type}">
            <div class="room-name">${t.type}</div>
            <div class="room-price">₹${t.price_per_night.toLocaleString('en-IN')}<small>/night</small></div>
            <div class="room-meta">Capacity: ${t.capacity} guests</div>
            <div class="room-total">Total for ${t.nights} night${t.nights>1?'s':''}: ₹${t.total_price.toLocaleString('en-IN')}</div>
            ${t.available_count > 1 ? `<span class="badge-avail">${t.available_count} left</span>` : ''}
            <div class="check-icon"><svg viewBox="0 0 12 12"><polyline points="1,6 5,10 11,2" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/></svg></div>
        `;
        card.addEventListener('click', () => selectRoom(card, t.type));
        grid.appendChild(card);
    });
}

function selectRoom(card, type) {
    document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('selectedRoomType').value = type;
    document.getElementById('btnSubmit').disabled = false;
    hideErr();
}
</script>
</body>
</html>
