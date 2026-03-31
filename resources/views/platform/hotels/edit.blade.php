@extends('layouts.platform')

@section('title', 'Edit Hotel — Platform Admin')
@section('page-title', 'Edit Hotel')
@section('page-subtitle'){{ $hotel->name }} — change settings, plan or status@endsection

@section('content')

<div style="max-width:720px;">

    {{-- Back link --}}
    <a href="{{ route('platform.hotels.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6d28d9;font-weight:600;text-decoration:none;margin-bottom:20px;">
        <i class="fas fa-arrow-left"></i> Back to Hotels
    </a>

    {{-- Status banner if suspended --}}
    @if($hotel->status === 'suspended')
    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="fas fa-ban" style="color:#b91c1c;"></i>
            <span style="font-size:13px;font-weight:700;color:#b91c1c;">This hotel is currently suspended. All staff logins are blocked.</span>
        </div>
        <form method="POST" action="{{ route('platform.hotels.activate', $hotel->id) }}" style="margin:0;">
            @csrf
            <button type="submit" style="padding:6px 14px;background:#15803d;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                <i class="fas fa-check-circle"></i> Reactivate
            </button>
        </form>
    </div>
    @endif

    <form method="POST" action="{{ route('platform.hotels.update', $hotel->id) }}">
        @csrf
        @method('PUT')

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
                    <input type="text" name="name" value="{{ old('name', $hotel->name) }}" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('name')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('name')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('name') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Email</label>
                    <input type="email" name="email" value="{{ old('email', $hotel->email) }}"
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('email')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('email')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('email') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $hotel->phone) }}"
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('phone')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('phone')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('phone') }}</p> @endif
                </div>

                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Address</label>
                    <textarea name="address" rows="2"
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('address')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;resize:vertical;">{{ old('address', $hotel->address) }}</textarea>
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

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
                @foreach($plans as $slug => $plan)
                @php $isCurrent = old('plan', $hotel->plan) === $slug; @endphp
                <label style="cursor:pointer;">
                    <input type="radio" name="plan" value="{{ $slug }}" {{ $isCurrent ? 'checked' : '' }} style="position:absolute;opacity:0;" class="plan-radio" data-max-rooms="{{ $plan['max_rooms'] == PHP_INT_MAX ? 999 : $plan['max_rooms'] }}" data-max-users="{{ $plan['max_users'] == PHP_INT_MAX ? 999 : $plan['max_users'] }}">
                    <div class="plan-card" data-plan="{{ $slug }}" style="border:2px solid {{ $isCurrent ? $plan['color'] : '#e2e8f0' }};border-radius:14px;padding:16px;transition:border-color .2s;background:{{ $isCurrent ? 'rgba(139,92,246,.04)' : '#fff' }};">
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
                    <input type="number" name="max_rooms" id="max_rooms" value="{{ old('max_rooms', $hotel->max_rooms) }}" min="1" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('max_rooms')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('max_rooms')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('max_rooms') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Max Users <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="max_users" id="max_users" value="{{ old('max_users', $hotel->max_users) }}" min="1" required
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('max_users')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('max_users')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('max_users') }}</p> @endif
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#10b981,#059669);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-toggle-on" style="color:#fff;font-size:12px;"></i>
                </span>
                Operational Status
            </h2>

            <div style="display:flex;gap:16px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 18px;border:2px solid {{ old('status',$hotel->status) === 'active' ? '#10b981' : '#e2e8f0' }};border-radius:12px;flex:1;transition:border-color .2s;" id="status-active-label">
                    <input type="radio" name="status" value="active" {{ old('status', $hotel->status) === 'active' ? 'checked' : '' }} onchange="updateStatusBorder()" style="accent-color:#10b981;">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#15803d;">Active</div>
                        <div style="font-size:11px;color:#64748b;">Staff can log in and use the CRM</div>
                    </div>
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 18px;border:2px solid {{ old('status',$hotel->status) === 'suspended' ? '#ef4444' : '#e2e8f0' }};border-radius:12px;flex:1;transition:border-color .2s;" id="status-suspended-label">
                    <input type="radio" name="status" value="suspended" {{ old('status', $hotel->status) === 'suspended' ? 'checked' : '' }} onchange="updateStatusBorder()" style="accent-color:#ef4444;">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#b91c1c;">Suspended</div>
                        <div style="font-size:11px;color:#64748b;">All logins blocked immediately</div>
                    </div>
                </label>
            </div>
            @if(isset($errors) && $errors->has('status')) <p style="color:#ef4444;font-size:11px;margin:8px 0 0;">{{ $errors->first('status') }}</p> @endif
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
                placeholder="Private notes visible only to platform admins...">{{ old('admin_notes', $hotel->admin_notes) }}</textarea>
        </div>

        {{-- Submit --}}
        <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;">
            <div style="display:flex;gap:12px;align-items:center;">
                <button type="submit" class="btn-primary" style="padding:12px 28px;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="{{ route('platform.hotels.index') }}" class="btn-secondary">
                    Cancel
                </a>
            </div>

            {{-- Quick suspend/activate from edit form --}}
            @if($hotel->status === 'active')
            <form method="POST" action="{{ route('platform.hotels.suspend', $hotel->id) }}" style="margin:0;" onsubmit="return confirm('Suspend {{ addslashes($hotel->name) }}? All staff logins will be blocked.')">
                @csrf
                <button type="submit" style="padding:10px 18px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-ban"></i> Suspend Hotel
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('platform.hotels.activate', $hotel->id) }}" style="margin:0;">
                @csrf
                <button type="submit" style="padding:10px 18px;background:#dcfce7;color:#15803d;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-check-circle"></i> Reactivate Hotel
                </button>
            </form>
            @endif
        </div>

    </form>
</div>

@push('scripts')
<script>
function updateStatusBorder() {
    var active    = document.querySelector('input[name="status"][value="active"]').checked;
    var suspended = document.querySelector('input[name="status"][value="suspended"]').checked;
    document.getElementById('status-active-label').style.borderColor    = active    ? '#10b981' : '#e2e8f0';
    document.getElementById('status-suspended-label').style.borderColor = suspended ? '#ef4444' : '#e2e8f0';
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
    });
});
</script>
@endpush

@endsection
