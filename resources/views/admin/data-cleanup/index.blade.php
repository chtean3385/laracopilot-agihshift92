@extends('layouts.admin')
@section('title', 'Data Cleanup')

@section('content')
<style>
/* ── Data Cleanup page styles ─────────────────────── */
.dc-wrap        { max-width:700px; }
.dc-card        { background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:20px; }
.dc-card-head   { background:#f8fafc;border-bottom:1.5px solid #e2e8f0;padding:14px 20px; }
.dc-group       { display:flex;align-items:center;gap:14px;padding:14px 20px;cursor:pointer;
                  border-bottom:1px solid #f1f5f9;transition:background .15s,border-color .15s; }
.dc-group:last-child { border-bottom:none; }
.dc-group:hover      { background:#fef2f2; }
.dc-group.selected   { background:#fff5f5;border-left:4px solid #dc2626;padding-left:16px; }
.dc-group input[type=checkbox] { display:none; }
.dc-tick  { width:22px;height:22px;border-radius:6px;border:2px solid #cbd5e1;background:#fff;
            flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .15s; }
.dc-group.selected .dc-tick { background:#dc2626;border-color:#dc2626; }
.dc-icon  { width:38px;height:38px;border-radius:10px;background:#fee2e2;flex-shrink:0;
            display:flex;align-items:center;justify-content:center;transition:background .15s; }
.dc-group.selected .dc-icon { background:#dc2626; }
.dc-group.selected .dc-icon i { color:#fff !important; }
.dc-label { font-size:14px;font-weight:600;color:#1e293b;margin-bottom:2px;line-height:1.3; }
.dc-desc  { font-size:12px;color:#64748b;line-height:1.4; }
.dc-warn  { background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:14px 18px;
            display:flex;align-items:flex-start;gap:12px;margin-bottom:20px; }
.dc-confirm-input { width:100%;max-width:260px;border:1.5px solid #fca5a5;border-radius:10px;
                    padding:10px 14px;font-family:monospace;font-size:15px;color:#991b1b;
                    letter-spacing:1.5px;outline:none;transition:border-color .2s; }
.dc-confirm-input:focus { border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.1); }
.dc-btn-clear { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#ef4444,#dc2626);
                color:#fff;border:none;border-radius:10px;padding:11px 26px;font-size:14px;
                font-weight:600;cursor:pointer;transition:all .2s;opacity:.45;pointer-events:none; }
.dc-btn-clear.ready { opacity:1;pointer-events:auto; }
.dc-btn-clear.ready:hover { transform:translateY(-1px);box-shadow:0 4px 12px rgba(220,38,38,.35); }
.dc-btn-cancel { display:inline-flex;align-items:center;gap:8px;background:#f8fafc;color:#374151;
                 border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 22px;font-size:14px;
                 font-weight:500;text-decoration:none;transition:background .15s; }
.dc-btn-cancel:hover { background:#f1f5f9;color:#1e293b; }
</style>

<div class="dc-wrap py-4">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#ef4444,#dc2626);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-trash3-fill text-white" style="font-size:20px;"></i>
        </div>
        <div>
            <h4 style="margin:0;font-weight:700;color:#1e293b;font-size:20px;">Data Cleanup</h4>
            <p style="margin:0;color:#64748b;font-size:13px;">Permanently delete selected data for this hotel only.</p>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div style="background:#dcfce7;border:1.5px solid #86efac;border-radius:12px;padding:12px 18px;
                display:flex;align-items:center;gap:10px;margin-bottom:18px;color:#166534;font-size:13.5px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background:#fee2e2;border:1.5px solid #fca5a5;border-radius:12px;padding:12px 18px;
                margin-bottom:18px;color:#991b1b;font-size:13.5px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <ul style="margin:6px 0 0 0;padding-left:18px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Warning --}}
    <div class="dc-warn">
        <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;font-size:18px;margin-top:1px;flex-shrink:0;"></i>
        <div style="font-size:13.5px;color:#92400e;line-height:1.6;">
            <strong>Danger Zone.</strong> Records deleted here are permanently removed.
            They <strong>cannot be recovered</strong> unless you have a separate backup.
        </div>
    </div>

    <form method="POST" action="{{ route('data-cleanup.truncate') }}" id="cleanupForm">
        @csrf

        {{-- Step 1 --}}
        <div class="dc-card">
            <div class="dc-card-head">
                <span style="font-size:13px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.5px;">
                    Step 1 &mdash; Select data to clear
                </span>
            </div>

            @php
            $groups = [
                ['key'=>'guests',   'icon'=>'bi-people-fill',            'label'=>'Guests',
                 'desc'=>'All customer / guest records for this hotel.'],
                ['key'=>'bookings', 'icon'=>'bi-calendar2-check-fill',   'label'=>'Bookings',
                 'desc'=>'Bookings including check-in, check-out history, add-ons and extra charges.'],
                ['key'=>'invoices', 'icon'=>'bi-receipt',                'label'=>'Invoices',
                 'desc'=>'All generated invoice records.'],
                ['key'=>'payments', 'icon'=>'bi-credit-card-fill',       'label'=>'Payments',
                 'desc'=>'All payment records — advance, balance, refund entries.'],
                ['key'=>'food',     'icon'=>'bi-cup-hot-fill',           'label'=>'Food & Beverage Billing',
                 'desc'=>'Food/beverage extra charges only. Bookings themselves are kept.'],
                ['key'=>'rooms',    'icon'=>'bi-door-open-fill',         'label'=>'Rooms',
                 'desc'=>'Room definitions and add-on configs. Existing bookings lose room reference.'],
            ];
            @endphp

            @foreach($groups as $g)
            <label class="dc-group" id="row_{{ $g['key'] }}" for="tbl_{{ $g['key'] }}">
                <input type="checkbox" name="tables[]" value="{{ $g['key'] }}" id="tbl_{{ $g['key'] }}">
                <div class="dc-tick">
                    <i class="bi bi-check2" style="color:#fff;font-size:13px;display:none;" id="chk_{{ $g['key'] }}"></i>
                </div>
                <div class="dc-icon">
                    <i class="bi {{ $g['icon'] }}" style="color:#dc2626;font-size:15px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="dc-label">{{ $g['label'] }}</div>
                    <div class="dc-desc">{{ $g['desc'] }}</div>
                </div>
            </label>
            @endforeach
        </div>

        {{-- Step 2 --}}
        <div class="dc-card" style="border-color:#fca5a5;">
            <div class="dc-card-head" style="background:#fff1f2;border-bottom-color:#fca5a5;">
                <span style="font-size:13px;font-weight:600;color:#991b1b;text-transform:uppercase;letter-spacing:.5px;">
                    Step 2 &mdash; Confirm deletion
                </span>
            </div>
            <div style="padding:18px 20px;">
                <p style="font-size:13.5px;color:#374151;margin:0 0 12px;">
                    Type <strong style="font-family:monospace;background:#fee2e2;padding:2px 6px;border-radius:4px;color:#991b1b;">DELETE</strong>
                    below to confirm this action is permanent.
                </p>
                <input type="text" name="confirm" id="confirmInput" class="dc-confirm-input"
                       placeholder="Type DELETE here" autocomplete="off" spellcheck="false">
                @error('confirm')
                    <div style="color:#dc2626;font-size:12.5px;margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;align-items:center;gap:12px;">
            <button type="submit" id="submitBtn" class="dc-btn-clear">
                <i class="bi bi-trash3-fill"></i>
                Clear Selected Data Permanently
            </button>
            <a href="{{ route('dashboard') }}" class="dc-btn-cancel">
                Cancel
            </a>
        </div>

    </form>
</div>

<script>
(function () {
    const form        = document.getElementById('cleanupForm');
    const confirmIn   = document.getElementById('confirmInput');
    const submitBtn   = document.getElementById('submitBtn');
    const checkboxes  = Array.from(document.querySelectorAll('input[name="tables[]"]'));

    function syncRow(cb) {
        const row  = document.getElementById('row_' + cb.value);
        const tick = document.getElementById('chk_' + cb.value);
        if (cb.checked) {
            row.classList.add('selected');
            tick.style.display = 'block';
        } else {
            row.classList.remove('selected');
            tick.style.display = 'none';
        }
    }

    function evaluate() {
        const typed     = confirmIn.value.trim();
        const anyTicked = checkboxes.some(c => c.checked);
        const ready     = typed === 'DELETE' && anyTicked;
        submitBtn.classList.toggle('ready', ready);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => { syncRow(cb); evaluate(); });
    });
    confirmIn.addEventListener('input', evaluate);

    form.addEventListener('submit', function (e) {
        if (confirmIn.value.trim() !== 'DELETE') { e.preventDefault(); return; }
        if (!confirm('Are you absolutely sure?\n\nThis will permanently delete the selected data. It cannot be undone.')) {
            e.preventDefault();
        }
    });
})();
</script>
@endsection
