<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .iti { width: 100%; }
        .iti input { padding-left: 54px !important; margin-bottom: 10px; }

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
    @csrf
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
    <input type="tel" name="phone" id="phoneInput" required placeholder="9876543210">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script>
const iti = window.intlTelInput(document.getElementById('phoneInput'), {
    initialCountry: '{{ strtolower($widgetSettings->default_country_code) }}',
    preferredCountries: ['in', 'us', 'gb', 'ae', 'sg'],
    utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js',
});

document.getElementById('iframeForm').addEventListener('submit', function(e) {
    const raw = document.getElementById('phoneInput').value.trim();
    if (raw && iti.isValidNumber()) {
        document.getElementById('phoneInput').value = iti.getNumber();
    } else if (raw) {
        e.preventDefault();
        showErr('Enter a valid phone number.');
    }
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

    fetch("{{ route('public.booking.availability', $hotel->slug) }}?check_in=" + ci + "&check_out=" + co, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('roomsLoading').style.display = 'none';
        if (!data.types || data.types.length === 0) {
            document.getElementById('noRooms').style.display = 'block';
            return;
        }
        renderRooms(data.types, data.nights);
        document.getElementById('roomsList').style.display = 'block';
    })
    .catch(() => {
        document.getElementById('roomsLoading').style.display = 'none';
        document.getElementById('roomsPlaceholder').style.display = 'block';
    });
}

function renderRooms(types, nights) {
    const list = document.getElementById('roomsList');
    list.innerHTML = '';
    types.forEach(t => {
        const card = document.createElement('div');
        card.className = 'room-card';
        card.innerHTML = `
            <div class="room-row">
                <div>
                    <div class="room-name">${t.type}</div>
                    <div class="room-sub">Up to ${t.capacity} guests · ${nights} night${nights>1?'s':''} total: ₹${t.total_price.toLocaleString('en-IN')}</div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="room-price">₹${t.price_per_night.toLocaleString('en-IN')}<small>/night</small></div>
                    <div class="check-circle"><svg viewBox="0 0 10 8"><polyline points="0,4 4,8 10,0"/></svg></div>
                </div>
            </div>
        `;
        card.addEventListener('click', () => {
            document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('selectedRoomType').value = t.type;
            document.getElementById('btnSubmit').disabled = false;
            hideErr();
        });
        list.appendChild(card);
    });
}
</script>
</body>
</html>
