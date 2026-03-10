@extends('layouts.admin')
@section('title','Channel Manager Config')
@section('page-title','Channel Manager Config')
@section('page-subtitle','Connect your OTA channel manager')

@section('content')
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    {{-- Left: Form --}}
    <div style="display:grid;gap:16px;">

        @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;color:#15803d;font-weight:600;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-check-circle"></i>{{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#dc2626;font-weight:600;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
        </div>
        @endif

        {{-- Provider Selection --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
            <h3 style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:16px;"><i class="fas fa-plug" style="color:#0891b2;margin-right:8px;"></i>Select Provider</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;">
                @foreach([
                    ['ezee',       'eZee Centrix',  '#e63946', 'Paid · Most popular in India'],
                    ['staah',      'STAAH',          '#2563eb', 'Paid · Popular for small hotels'],
                    ['siteminder', 'SiteMinder',     '#7c3aed', 'Paid · Global enterprise'],
                    ['rategain',   'RateGain',       '#0891b2', 'Paid · Popular in India'],
                ] as [$slug, $label, $color, $tagline])
                <label id="provider-card-{{ $slug }}" style="cursor:pointer;border:2px solid {{ $config->provider === $slug ? $color : '#e2e8f0' }};border-radius:12px;padding:14px;text-align:center;transition:border .15s;background:{{ $config->provider === $slug ? $color.'0d' : '#fff' }};">
                    <input type="radio" name="provider_select" value="{{ $slug }}" {{ $config->provider === $slug ? 'checked' : '' }} style="display:none;" onchange="selectProvider('{{ $slug }}')">
                    <div style="font-size:22px;margin-bottom:6px;">
                        @if($slug === 'ezee') 🏨
                        @elseif($slug === 'staah') 🔗
                        @elseif($slug === 'siteminder') 🌐
                        @else 📊
                        @endif
                    </div>
                    <div style="font-weight:800;font-size:13px;color:#1e293b;">{{ $label }}</div>
                    <div style="font-size:10px;color:#64748b;margin-top:3px;">{{ $tagline }}</div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Credentials --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
            <h3 style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:16px;"><i class="fas fa-key" style="color:#f59e0b;margin-right:8px;"></i>API Credentials</h3>
            <form action="{{ route('channel_manager.config.save') }}" method="POST">
                @csrf
                <input type="hidden" name="provider" id="providerInput" value="{{ $config->provider ?? 'ezee' }}">
                <div style="display:grid;gap:14px;">
                    <div>
                        <label class="form-label">API Key / Access Token <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="api_key" value="{{ old('api_key', $config->api_key) }}"
                            class="form-input" placeholder="Paste token only — do NOT include 'Bearer ' prefix">
                    </div>
                    <div id="field-api-secret">
                        <label class="form-label" id="label-api-secret">API Secret</label>
                        <input type="text" name="api_secret" value="{{ old('api_secret', $config->api_secret) }}"
                            class="form-input" placeholder="Client secret or API secret">
                    </div>
                    <div id="field-hotel-code">
                        <label class="form-label" id="label-hotel-code">Hotel Code</label>
                        <input type="text" name="hotel_code" value="{{ old('hotel_code', $config->hotel_code) }}"
                            class="form-input" placeholder="Your hotel code in the channel manager">
                    </div>
                    <div id="field-property-id">
                        <label class="form-label" id="label-property-id">Property ID</label>
                        <input type="text" name="property_id" value="{{ old('property_id', $config->property_id) }}"
                            class="form-input" placeholder="Property / Client ID">
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#f8fafc;border-radius:10px;">
                        <input type="checkbox" name="is_active" id="isActive" value="1" {{ $config->is_active ? 'checked' : '' }} style="width:18px;height:18px;cursor:pointer;">
                        <label for="isActive" style="font-weight:700;font-size:13px;color:#1e293b;cursor:pointer;">Enable channel manager</label>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" style="flex:1;padding:11px;background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;">
                            <i class="fas fa-save" style="margin-right:6px;"></i>Save Configuration
                        </button>
                        <button type="button" onclick="testConnection()" style="padding:11px 18px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;">
                            <i class="fas fa-plug" style="margin-right:5px;"></i>Test
                        </button>
                    </div>
                </div>
            </form>
            <div id="testResult" style="display:none;margin-top:12px;padding:12px;border-radius:10px;font-weight:600;font-size:13px;"></div>
        </div>

    </div>

    {{-- Right: Setup Guide --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:20px;position:sticky;top:20px;">
        <h3 style="font-weight:800;font-size:14px;color:#1e293b;margin-bottom:16px;"><i class="fas fa-book" style="color:#0891b2;margin-right:8px;"></i>Setup Guide</h3>

        @foreach([
            'ezee' => [
                ['Sign up at eZee Centrix', 'https://www.ezeetechnosys.com', 'Create an account and connect your property'],
                ['Get Auth Code', 'https://www.ezeetechnosys.com', 'Settings → API Access → Copy your Auth Code'],
                ['Get Hotel Code', 'https://www.ezeetechnosys.com', 'Your hotel code is shown in the eZee dashboard URL'],
                ['Map Room Types', '#', 'Go to Room Mapping to link CRM rooms to eZee room types'],
            ],
            'staah' => [
                ['Sign up at STAAH', 'https://www.staah.com', 'Create a STAAH account for your property'],
                ['Get API Key', 'https://app.staah.com/settings', 'Settings → API → Generate access token'],
                ['Get Client Secret', 'https://app.staah.com/settings', 'Copy the client secret from same settings page'],
                ['Get Property ID', 'https://app.staah.com', 'Property ID is in your STAAH dashboard URL'],
            ],
            'siteminder' => [
                ['Sign up at SiteMinder', 'https://www.siteminder.com', 'Contact SiteMinder sales for a property account'],
                ['Get API Key', 'https://app.siteminder.com/settings', 'Settings → API Keys → Generate new key'],
                ['Get Property ID', 'https://app.siteminder.com', 'Found in your SiteMinder account settings'],
                ['Map Room Types', '#', 'Use the Room Mapping page to link CRM rooms'],
            ],
            'rategain' => [
                ['Sign up at RateGain', 'https://www.rategain.com', 'Contact RateGain for a property account'],
                ['Get API Credentials', 'https://app.rategain.com/settings', 'API credentials provided by RateGain support'],
                ['Get Hotel Code', 'https://app.rategain.com', 'Hotel code shown in RateGain dashboard'],
                ['Map Room Types', '#', 'Use the Room Mapping page after setup'],
            ],
        ] as $provSlug => $steps)
        <div id="guide-{{ $provSlug }}" style="display:{{ ($config->provider ?? 'ezee') === $provSlug ? 'block' : 'none' }};">
            @foreach($steps as $i => [$title, $link, $desc])
            <div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-start;">
                <div style="width:22px;height:22px;background:linear-gradient(135deg,#0891b2,#0e7490);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                <div>
                    <a href="{{ $link }}" target="_blank" style="font-weight:700;font-size:12px;color:#0891b2;text-decoration:none;">{{ $title }} →</a>
                    <p style="font-size:11px;color:#64748b;margin:2px 0 0;">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endforeach

        <div style="margin-top:16px;padding:12px;background:#fef9c3;border-radius:10px;">
            <p style="font-size:11px;color:#854d0e;font-weight:600;"><i class="fas fa-lightbulb" style="margin-right:5px;"></i>Tip: All providers require an approved WhatsApp Business or hotel registration. Free-tier accounts may have limited API access.</p>
        </div>
    </div>

</div>

<script>
const providerFields = {
    ezee:       { secret: false, hotel: true,  property: false, hotelLabel: 'Hotel Code',   secretLabel: '' },
    staah:      { secret: true,  hotel: false, property: true,  propertyLabel: 'Property ID', secretLabel: 'Client Secret' },
    siteminder: { secret: false, hotel: false, property: true,  propertyLabel: 'Property ID', secretLabel: '' },
    rategain:   { secret: false, hotel: true,  property: false, hotelLabel: 'Hotel Code',   secretLabel: '' },
};

function selectProvider(slug) {
    document.getElementById('providerInput').value = slug;
    const f = providerFields[slug] || {};
    document.getElementById('field-api-secret').style.display  = f.secret ? 'block' : 'none';
    document.getElementById('field-hotel-code').style.display  = f.hotel ? 'block' : 'none';
    document.getElementById('field-property-id').style.display = f.property ? 'block' : 'none';
    if (f.secretLabel)  document.getElementById('label-api-secret').textContent  = f.secretLabel;
    if (f.hotelLabel)   document.getElementById('label-hotel-code').textContent   = f.hotelLabel;
    if (f.propertyLabel) document.getElementById('label-property-id').textContent = f.propertyLabel;
    document.querySelectorAll('[id^="provider-card-"]').forEach(card => {
        const cardSlug = card.id.replace('provider-card-', '');
        const colors = { ezee:'#e63946', staah:'#2563eb', siteminder:'#7c3aed', rategain:'#0891b2' };
        const c = colors[cardSlug] || '#0891b2';
        if (cardSlug === slug) {
            card.style.border = '2px solid ' + c;
            card.style.background = c + '0d';
        } else {
            card.style.border = '2px solid #e2e8f0';
            card.style.background = '#fff';
        }
        document.querySelectorAll('[id^="guide-"]').forEach(g => g.style.display = 'none');
        const g = document.getElementById('guide-' + slug);
        if (g) g.style.display = 'block';
    });
}
selectProvider('{{ $config->provider ?? "ezee" }}');

function testConnection() {
    const btn = event.target;
    btn.disabled = true; btn.textContent = 'Testing…';
    const res = document.getElementById('testResult');
    res.style.display = 'none';
    fetch('{{ route("channel_manager.config.test") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(d => {
        res.style.display = 'block';
        res.style.background = d.success ? '#f0fdf4' : '#fef2f2';
        res.style.color = d.success ? '#15803d' : '#dc2626';
        res.style.border = '1px solid ' + (d.success ? '#bbf7d0' : '#fecaca');
        res.innerHTML = '<i class="fas fa-' + (d.success ? 'check-circle' : 'exclamation-circle') + '" style="margin-right:6px;"></i>' + d.message;
        btn.disabled = false; btn.textContent = 'Test';
    })
    .catch(() => { btn.disabled = false; btn.textContent = 'Test'; });
}
</script>
@endsection
