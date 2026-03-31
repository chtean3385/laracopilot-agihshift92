@extends('layouts.platform')

@section('title', 'New Hotel — Platform Admin')
@section('page-title', 'Create New Hotel')
@section('page-subtitle', 'Provision a new tenant — hotel + settings + modules + roles in one step')

@section('content')

<div style="max-width:720px;">

    {{-- Back link --}}
    <a href="{{ route('platform.hotels.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6d28d9;font-weight:600;text-decoration:none;margin-bottom:20px;">
        <i class="fas fa-arrow-left"></i> Back to Hotels
    </a>

    <form method="POST" action="{{ route('platform.hotels.store') }}">
        @csrf

        {{-- Basic Info --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-building" style="color:#fff;font-size:12px;"></i>
                </span>
                Hotel Details
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Hotel Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('name')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="e.g. Grand Horizon Resort">
                    @if(isset($errors) && $errors->has('name')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('name') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('email')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="admin@hotel.com">
                    @if(isset($errors) && $errors->has('email')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('email') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('phone')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="+91 79 1234 5678">
                    @if(isset($errors) && $errors->has('phone')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('phone') }}</p> @endif
                </div>

                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Address</label>
                    <textarea name="address" rows="2"
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('address')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;resize:vertical;"
                        placeholder="123 Main Street, City, State 380001">{{ old('address') }}</textarea>
                    @if(isset($errors) && $errors->has('address')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('address') }}</p> @endif
                </div>
            </div>
        </div>

        {{-- Plan & Limits --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#06b6d4,#0891b2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-layer-group" style="color:#fff;font-size:12px;"></i>
                </span>
                Plan & Limits
            </h2>

            {{-- Plan selector cards --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
                @foreach($plans as $slug => $plan)
                <label style="cursor:pointer;">
                    <input type="radio" name="plan" value="{{ $slug }}" {{ old('plan','basic') === $slug ? 'checked' : '' }} style="position:absolute;opacity:0;" class="plan-radio" data-max-rooms="{{ $plan['max_rooms'] == PHP_INT_MAX ? 999 : $plan['max_rooms'] }}" data-max-users="{{ $plan['max_users'] == PHP_INT_MAX ? 999 : $plan['max_users'] }}">
                    <div class="plan-card" data-plan="{{ $slug }}" style="border:2px solid {{ old('plan','basic') === $slug ? $plan['color'] : '#e2e8f0' }};border-radius:14px;padding:16px;transition:border-color .2s;background:{{ old('plan','basic') === $slug ? 'rgba(139,92,246,.04)' : '#fff' }};">
                        <div style="font-size:13px;font-weight:800;color:{{ $plan['color'] }};margin-bottom:4px;">{{ $plan['label'] }}</div>
                        <div style="font-size:11px;color:#64748b;">{{ $plan['limits_note'] }}</div>
                    </div>
                </label>
                @endforeach
            </div>
            @if(isset($errors) && $errors->has('plan')) <p style="color:#ef4444;font-size:11px;margin:-12px 0 12px;">{{ $errors->first('plan') }}</p> @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Max Rooms <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="max_rooms" id="max_rooms" value="{{ old('max_rooms', 50) }}" min="1" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('max_rooms')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('max_rooms')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('max_rooms') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Max Users <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="max_users" id="max_users" value="{{ old('max_users', 10) }}" min="1" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('max_users')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('max_users')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('max_users') }}</p> @endif
                </div>
            </div>
        </div>

        {{-- Admin Notes --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:24px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-sticky-note" style="color:#fff;font-size:12px;"></i>
                </span>
                Internal Notes
            </h2>
            <textarea name="admin_notes" rows="3"
                style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;resize:vertical;"
                placeholder="Private notes visible only to platform admins...">{{ old('admin_notes') }}</textarea>
        </div>

        {{-- Provisioning info box --}}
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:16px;margin-bottom:24px;">
            <div style="display:flex;gap:10px;align-items:flex-start;">
                <i class="fas fa-info-circle" style="color:#15803d;margin-top:2px;flex-shrink:0;"></i>
                <div style="font-size:13px;color:#15803d;line-height:1.6;">
                    <strong>Atomic provisioning:</strong> Submitting this form will create the hotel + default settings + 4 modules (WhatsApp, Payment Links, Pathik, Channel Manager) + 3 system roles (Admin, Manager, Receptionist) in a single database transaction. If any step fails, nothing is created.
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div style="display:flex;gap:12px;align-items:center;">
            <button type="submit" class="btn-primary" style="padding:12px 28px;">
                <i class="fas fa-plus"></i> Create & Provision Hotel
            </button>
            <a href="{{ route('platform.hotels.index') }}" class="btn-secondary">
                Cancel
            </a>
        </div>

    </form>
</div>

@push('scripts')
<script>
document.querySelectorAll('.plan-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        // Update card borders
        document.querySelectorAll('.plan-card').forEach(function(card) {
            card.style.borderColor = '#e2e8f0';
            card.style.background = '#fff';
        });
        var card = document.querySelector('.plan-card[data-plan="' + this.value + '"]');
        if (card) {
            card.style.borderColor = '#7c3aed';
            card.style.background = 'rgba(139,92,246,.04)';
        }
        // Pre-fill limits if default hasn't been changed
        var maxRooms = parseInt(this.dataset.maxRooms);
        var maxUsers = parseInt(this.dataset.maxUsers);
        if (maxRooms && maxRooms < 999) document.getElementById('max_rooms').value = maxRooms;
        if (maxUsers && maxUsers < 999) document.getElementById('max_users').value = maxUsers;
    });
});
</script>
@endpush

@endsection
