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
                        @if(isset($plan['monthly_price']))
                        <div style="margin-top:8px;font-size:12px;font-weight:700;color:#1e293b;">Rs {{ number_format($plan['monthly_price']) }}<span style="font-size:10px;font-weight:500;color:#94a3b8;">/mo</span></div>
                        <div style="font-size:10px;color:#94a3b8;">Rs {{ number_format($plan['yearly_price']) }}/yr</div>
                        @endif
                    </div>
                </label>
                @endforeach
            </div>
            @if(isset($errors) && $errors->has('plan')) <p style="color:#ef4444;font-size:11px;margin:-12px 0 12px;">{{ $errors->first('plan') }}</p> @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
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

            {{-- Billing Cycle --}}
            <div style="border-top:1px solid #f1f5f9;padding-top:20px;margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;">Billing Cycle <span style="color:#ef4444;">*</span></label>
                <div style="display:flex;gap:12px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 18px;border:2px solid {{ old('billing_cycle','monthly') === 'monthly' ? '#7c3aed' : '#e2e8f0' }};border-radius:10px;flex:1;" id="cycle-monthly-label">
                        <input type="radio" name="billing_cycle" value="monthly" {{ old('billing_cycle','monthly') === 'monthly' ? 'checked' : '' }} onchange="updateCycleBorder()" style="accent-color:#7c3aed;">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1e293b;">Monthly</div>
                            <div style="font-size:11px;color:#64748b;">Billed each month</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 18px;border:2px solid {{ old('billing_cycle','monthly') === 'yearly' ? '#7c3aed' : '#e2e8f0' }};border-radius:10px;flex:1;" id="cycle-yearly-label">
                        <input type="radio" name="billing_cycle" value="yearly" {{ old('billing_cycle','monthly') === 'yearly' ? 'checked' : '' }} onchange="updateCycleBorder()" style="accent-color:#7c3aed;">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1e293b;">Yearly</div>
                            <div style="font-size:11px;color:#64748b;">Billed once per year</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Custom Pricing Override --}}
            <div style="background:#f8fafc;border:1.5px dashed #c7d2fe;border-radius:12px;padding:16px;">
                <div style="font-size:12px;font-weight:700;color:#4338ca;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-tag"></i> Custom Pricing Override <span style="font-size:10px;font-weight:500;color:#94a3b8;">(optional — leave blank to use plan default)</span>
                </div>
                <div style="font-size:11px;color:#64748b;margin-bottom:14px;">Override the plan price for this specific hotel. Useful for special deals or promotions.</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Custom Monthly Price (Rs)</label>
                        <input type="number" name="custom_monthly_price" id="custom_monthly_price" value="{{ old('custom_monthly_price') }}" min="0"
                            placeholder="e.g. 499"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;box-sizing:border-box;outline:none;">
                        <div id="plan_monthly_hint" style="font-size:10px;color:#94a3b8;margin-top:4px;"></div>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Custom Yearly Price (Rs)</label>
                        <input type="number" name="custom_yearly_price" id="custom_yearly_price" value="{{ old('custom_yearly_price') }}" min="0"
                            placeholder="e.g. 4999"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;box-sizing:border-box;outline:none;">
                        <div id="plan_yearly_hint" style="font-size:10px;color:#94a3b8;margin-top:4px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Admin User --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#10b981,#059669);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-user-shield" style="color:#fff;font-size:12px;"></i>
                </span>
                Hotel Admin Account
            </h2>
            <p style="font-size:12px;color:#64748b;margin:-12px 0 16px;">This user will be the hotel admin and can log in to the CRM immediately.</p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Admin Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('admin_name')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="e.g. Rajesh Kumar">
                    @if(isset($errors) && $errors->has('admin_name')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('admin_name') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Admin Email <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('admin_email')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="admin@hotelname.com">
                    @if(isset($errors) && $errors->has('admin_email')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('admin_email') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Admin Password <span style="color:#ef4444;">*</span></label>
                    <input type="password" name="admin_password" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('admin_password')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="Min 6 characters" autocomplete="new-password">
                    @if(isset($errors) && $errors->has('admin_password')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('admin_password') }}</p> @endif
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
var planPrices = {!! json_encode(collect($plans)->map(fn($p) => ['monthly' => $p['monthly_price'] ?? 0, 'yearly' => $p['yearly_price'] ?? 0])) !!};

function updatePriceHints(slug) {
    var prices = planPrices[slug];
    if (!prices) return;
    var mHint = document.getElementById('plan_monthly_hint');
    var yHint = document.getElementById('plan_yearly_hint');
    if (mHint) mHint.textContent = 'Plan default: Rs ' + prices.monthly.toLocaleString('en-IN') + '/mo';
    if (yHint) yHint.textContent = 'Plan default: Rs ' + prices.yearly.toLocaleString('en-IN') + '/yr';
}

function updateCycleBorder() {
    var monthly = document.querySelector('input[name="billing_cycle"][value="monthly"]').checked;
    document.getElementById('cycle-monthly-label').style.borderColor = monthly ? '#7c3aed' : '#e2e8f0';
    document.getElementById('cycle-yearly-label').style.borderColor  = monthly ? '#e2e8f0' : '#7c3aed';
}

document.querySelectorAll('.plan-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.plan-card').forEach(function(card) {
            card.style.borderColor = '#e2e8f0';
            card.style.background = '#fff';
        });
        var card = document.querySelector('.plan-card[data-plan="' + this.value + '"]');
        if (card) {
            card.style.borderColor = '#7c3aed';
            card.style.background = 'rgba(139,92,246,.04)';
        }
        var maxRooms = parseInt(this.dataset.maxRooms);
        var maxUsers = parseInt(this.dataset.maxUsers);
        if (maxRooms && maxRooms < 999) document.getElementById('max_rooms').value = maxRooms;
        if (maxUsers && maxUsers < 999) document.getElementById('max_users').value = maxUsers;
        updatePriceHints(this.value);
    });
});

// Init hint for default selected plan
var defaultPlan = document.querySelector('.plan-radio:checked');
if (defaultPlan) updatePriceHints(defaultPlan.value);
</script>
@endpush

@endsection
