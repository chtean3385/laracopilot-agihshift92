@extends('layouts.admin')
@section('title', 'Data Cleanup')

@section('content')
<div class="container-fluid py-4" style="max-width:720px;">

    {{-- Page header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#ef4444,#dc2626);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-trash3-fill text-white fs-5"></i>
        </div>
        <div>
            <h4 class="mb-0 fw-bold" style="color:#1e293b;">Data Cleanup</h4>
            <p class="mb-0 text-muted" style="font-size:13px;">Permanently delete selected data for this hotel. This cannot be undone.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#dcfce7;color:#166534;">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" style="border-radius:12px;border:none;background:#fee2e2;color:#991b1b;">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Warning banner --}}
    <div class="mb-4 p-3 d-flex align-items-start gap-3"
         style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1 flex-shrink-0"></i>
        <div style="font-size:13.5px;color:#92400e;line-height:1.6;">
            <strong>Danger Zone.</strong> Records deleted here are permanently removed from the database.
            They <strong>cannot be recovered</strong> unless you have a separate backup.
            Only delete data you are absolutely certain you no longer need.
        </div>
    </div>

    <form method="POST" action="{{ route('data-cleanup.truncate') }}" id="cleanupForm">
        @csrf

        {{-- Table selection --}}
        <div class="card mb-4" style="border-radius:16px;border:1.5px solid #e2e8f0;">
            <div class="card-header py-3 px-4" style="background:#f8fafc;border-bottom:1.5px solid #e2e8f0;border-radius:16px 16px 0 0;">
                <h6 class="mb-0 fw-semibold" style="color:#374151;">Step 1 — Select data groups to clear</h6>
            </div>
            <div class="card-body p-0">

                @php
                $groups = [
                    ['key'=>'guests',   'icon'=>'bi-person-fill',         'label'=>'Guests',
                     'desc'=>'All guest/customer records for this hotel.'],
                    ['key'=>'bookings', 'icon'=>'bi-calendar2-check-fill','label'=>'Bookings',
                     'desc'=>'All booking records including check-in & check-out history, add-ons, and extra charges.'],
                    ['key'=>'invoices', 'icon'=>'bi-receipt',             'label'=>'Invoices',
                     'desc'=>'All generated invoices.'],
                    ['key'=>'payments', 'icon'=>'bi-credit-card-fill',    'label'=>'Payments',
                     'desc'=>'All payment records (advance, balance, refund entries).'],
                    ['key'=>'food',     'icon'=>'bi-cup-hot-fill',        'label'=>'Food & Beverage Billing',
                     'desc'=>'Extra charges with category "Food & Beverage" only. Booking records are kept.'],
                    ['key'=>'rooms',    'icon'=>'bi-door-open-fill',      'label'=>'Rooms',
                     'desc'=>'All room definitions and their add-on configurations. Bookings are not deleted but will lose room references.'],
                ];
                @endphp

                @foreach($groups as $i => $g)
                <label for="tbl_{{ $g['key'] }}"
                       class="d-flex align-items-start gap-3 px-4 py-3 w-100 user-select-none group-row"
                       style="cursor:pointer;border-bottom:{{ !$loop->last ? '1px solid #f1f5f9' : 'none' }};transition:background .15s;">
                    <input type="checkbox" name="tables[]" value="{{ $g['key'] }}"
                           id="tbl_{{ $g['key'] }}" class="form-check-input mt-1 flex-shrink-0"
                           style="width:18px;height:18px;accent-color:#dc2626;">
                    <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi {{ $g['icon'] }}" style="color:#dc2626;font-size:15px;"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:14px;color:#1e293b;">{{ $g['label'] }}</div>
                        <div style="font-size:12.5px;color:#64748b;line-height:1.5;">{{ $g['desc'] }}</div>
                    </div>
                </label>
                @endforeach

            </div>
        </div>

        {{-- Confirmation --}}
        <div class="card mb-4" style="border-radius:16px;border:1.5px solid #fca5a5;">
            <div class="card-header py-3 px-4" style="background:#fff1f2;border-bottom:1.5px solid #fca5a5;border-radius:16px 16px 0 0;">
                <h6 class="mb-0 fw-semibold" style="color:#991b1b;">Step 2 — Confirm deletion</h6>
            </div>
            <div class="card-body px-4 py-3">
                <p style="font-size:13.5px;color:#374151;margin-bottom:10px;">
                    Type <strong>DELETE</strong> in the box below to confirm you understand this action is permanent.
                </p>
                <input type="text" name="confirm" id="confirmInput"
                       class="form-control @error('confirm') is-invalid @enderror"
                       placeholder="Type DELETE here" autocomplete="off"
                       style="max-width:280px;border-radius:10px;font-family:monospace;font-size:15px;
                              border:1.5px solid #fca5a5;color:#991b1b;letter-spacing:1px;">
                @error('confirm')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Submit --}}
        <div class="d-flex align-items-center gap-3">
            <button type="submit" id="submitBtn"
                    class="btn btn-danger d-flex align-items-center gap-2"
                    style="border-radius:10px;font-weight:600;padding:10px 24px;font-size:14px;opacity:.5;"
                    disabled>
                <i class="bi bi-trash3-fill"></i>
                Clear Selected Data Permanently
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-light"
               style="border-radius:10px;padding:10px 20px;font-size:14px;border:1.5px solid #e2e8f0;">
                Cancel
            </a>
        </div>

    </form>
</div>

<style>
.group-row:hover { background:#fef2f2; }
.form-check-input:checked + div + div .fw-semibold { color:#dc2626; }
</style>

<script>
(function () {
    const confirmInput = document.getElementById('confirmInput');
    const submitBtn    = document.getElementById('submitBtn');
    const checkboxes   = document.querySelectorAll('input[name="tables[]"]');

    function evaluate() {
        const typed    = confirmInput.value.trim();
        const anyChecked = Array.from(checkboxes).some(c => c.checked);
        const ready    = typed === 'DELETE' && anyChecked;
        submitBtn.disabled = !ready;
        submitBtn.style.opacity = ready ? '1' : '.5';
    }

    confirmInput.addEventListener('input', evaluate);
    checkboxes.forEach(c => c.addEventListener('change', evaluate));

    document.getElementById('cleanupForm').addEventListener('submit', function (e) {
        const typed = confirmInput.value.trim();
        if (typed !== 'DELETE') { e.preventDefault(); return; }
        if (!confirm('Are you absolutely sure? This will permanently delete the selected data and cannot be undone.')) {
            e.preventDefault();
        }
    });
})();
</script>
@endsection
