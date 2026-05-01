@extends('layouts.platform')
@section('title', 'Manage Hotel WhatsApp Numbers')

@section('content')
<div style="max-width:900px;margin:0 auto;">

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">Hotel WhatsApp Numbers</h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">Register each hotel's own WhatsApp number under your business account. One WABA, all hotels.</p>
    </div>
    @if($platform && $platform->saas_token && $platform->saas_waba_id)
    <button onclick="openAddModal()" style="background:#25D366;color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-plus"></i> Add Hotel Number
    </button>
    @endif
</div>

{{-- Platform not configured warning --}}
@if(!$platform || !$platform->saas_token || !$platform->saas_waba_id)
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:18px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px;">
    <i class="fas fa-exclamation-triangle" style="color:#d97706;margin-top:2px;font-size:16px;"></i>
    <div>
        <div style="font-size:14px;font-weight:700;color:#92400e;">Platform credentials not configured</div>
        <div style="font-size:13px;color:#b45309;margin-top:4px;">You must save your <strong>Permanent Access Token</strong> and <strong>WABA ID</strong> in <a href="{{ route('platform.whatsapp.settings') }}" style="color:#d97706;text-decoration:underline;">Platform Settings</a> before registering hotel numbers.</div>
    </div>
</div>
@endif

{{-- How it works --}}
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:16px 20px;margin-bottom:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
    @foreach([
        ['1','Add Number','Enter hotel\'s WhatsApp number + display name','#16a34a'],
        ['2','OTP Sent','Meta sends a verification SMS to that number','#0284c7'],
        ['3','Enter OTP','Hotel owner shares the 6-digit code with you','#7c3aed'],
        ['4','Active!','Messages route through their number via your app','#15803d'],
    ] as [$step,$title,$desc,$color])
    <div style="display:flex;gap:10px;align-items:flex-start;">
        <div style="width:28px;height:28px;background:{{$color}};border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-size:12px;font-weight:800;">{{$step}}</div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#111827;">{{$title}}</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;line-height:1.4;">{{$desc}}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Hotels table --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;">
    <div style="padding:18px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:10px;">
        <i class="fab fa-whatsapp" style="color:#25D366;font-size:18px;"></i>
        <span style="font-size:15px;font-weight:700;color:#111827;">All Hotels</span>
        <span style="background:#f3f4f6;color:#6b7280;font-size:12px;font-weight:600;padding:2px 10px;border-radius:20px;">{{ $hotels->count() }} hotels</span>
    </div>

    @forelse($hotels as $hotel)
    @php
        $cfg = $configs->get($hotel->id);
        $sharedWith = ($cfg && $cfg->phone_number_id) ? ($sharedMap[$cfg->phone_number_id] ?? collect()) : collect();
        $isShared = $sharedWith->count() > 1;
    @endphp
    <div style="padding:18px 24px;border-bottom:1px solid #f9fafb;display:flex;align-items:center;gap:16px;">
        {{-- Hotel info --}}
        <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:700;color:#111827;display:flex;align-items:center;gap:8px;">
                {{ $hotel->name }}
                @if($isShared)
                <span style="background:#f5f3ff;color:#7c3aed;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;border:1px solid #e9d5ff;">
                    <i class="fas fa-link" style="font-size:9px;"></i> Shared
                </span>
                @endif
            </div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">ID #{{ $hotel->id }}
                @if($cfg && $cfg->phone_number)
                &nbsp;·&nbsp; +{{ $cfg->phone_number }}
                @endif
                @if($cfg && $cfg->managed_display_name)
                &nbsp;·&nbsp; {{ $cfg->managed_display_name }}
                @endif
                @if($isShared)
                &nbsp;·&nbsp; <span style="color:#7c3aed;">shared with {{ $sharedWith->filter(fn($n) => $n !== $hotel->name)->implode(', ') }}</span>
                @endif
            </div>
        </div>

        {{-- Status badge --}}
        <div style="flex-shrink:0;">
            @if(!$cfg || $cfg->mode !== 'managed')
                <span style="background:#f3f4f6;color:#6b7280;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">
                    <i class="fas fa-minus" style="font-size:9px;"></i> No Managed Number
                </span>
            @elseif($cfg->managed_otp_status === 'pending')
                <span style="background:#fef3c7;color:#92400e;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;">
                    <i class="fas fa-clock" style="font-size:9px;"></i> OTP Pending
                </span>
            @elseif($cfg->is_active && $cfg->setup_completed)
                <span style="background:#dcfce7;color:#15803d;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;">
                    <i class="fas fa-check-circle" style="font-size:10px;"></i> Active
                </span>
            @else
                <span style="background:#f3f4f6;color:#6b7280;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Inactive</span>
            @endif
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:8px;flex-shrink:0;">
            @if(!$cfg || $cfg->mode !== 'managed')
                @if($platform && $platform->saas_token && $platform->saas_waba_id)
                <button onclick="openAddModal({{ $hotel->id }}, '{{ addslashes($hotel->name) }}')"
                    style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-plus"></i> Add Number
                </button>
                @endif
            @elseif($cfg->managed_otp_status === 'pending')
                <button onclick="openVerifyModal({{ $cfg->id }}, '+{{ $cfg->phone_number }}')"
                    style="background:#7c3aed;color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-key"></i> Enter OTP
                </button>
                <button onclick="resendOtp({{ $cfg->id }}, this)"
                    style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-redo"></i> Resend
                </button>
            @elseif($cfg->is_active)
                <button onclick="openVerifyModal({{ $cfg->id }}, '+{{ $cfg->phone_number }}')"
                    style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-key"></i> Re-verify
                </button>
            @endif

            @if($cfg && $cfg->mode === 'managed')
            <button onclick="removeNumber({{ $cfg->id }}, '{{ addslashes($hotel->name) }}')"
                style="background:#fff0f0;color:#dc2626;border:1px solid #fca5a5;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    </div>
    @empty
    <div style="padding:40px 24px;text-align:center;color:#9ca3af;font-size:14px;">
        No hotels found. Add hotels first from the Hotels section.
    </div>
    @endforelse
</div>

{{-- ===== ADD NUMBER MODAL ===== --}}
<div id="addModal" style="display:none;position:fixed;inset:0;z-index:9000;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);" onclick="closeAddModal()"></div>
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
        <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:520px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.2);pointer-events:auto;max-height:90vh;overflow-y:auto;">
            <button onclick="closeAddModal()" style="position:absolute;top:16px;right:16px;background:#f3f4f6;border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:18px;color:#6b7280;line-height:1;">×</button>

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                <div style="width:44px;height:44px;background:#25D366;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fab fa-whatsapp" style="color:#fff;font-size:22px;"></i>
                </div>
                <div>
                    <div style="font-size:17px;font-weight:800;color:#111827;">Add Hotel Number</div>
                    <div id="addModalHotelName" style="font-size:13px;color:#6b7280;">Select a hotel below</div>
                </div>
            </div>

            {{-- Tab switcher --}}
            <div style="display:flex;background:#f3f4f6;border-radius:10px;padding:4px;margin-bottom:20px;gap:4px;">
                <button id="tabNewBtn" onclick="switchTab('new')"
                    style="flex:1;padding:8px 12px;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;background:#fff;color:#111827;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fas fa-plus-circle" style="color:#25D366;margin-right:5px;"></i>Register New Number
                </button>
                <button id="tabLinkBtn" onclick="switchTab('link')"
                    style="flex:1;padding:8px 12px;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;background:transparent;color:#6b7280;">
                    <i class="fas fa-link" style="color:#7c3aed;margin-right:5px;"></i>Use Existing Number
                </button>
            </div>

            {{-- TAB: Register New --}}
            <div id="tabNew">
                <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:#92400e;line-height:1.6;">
                    <i class="fas fa-info-circle"></i> &nbsp;Meta will send an <strong>OTP via SMS</strong> to the hotel's WhatsApp number. The hotel owner needs to share that code with you to complete verification.
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Hotel</label>
                    <select id="addHotelId" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;">
                        <option value="">— Select Hotel —</option>
                        @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}">{{ $hotel->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:16px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Country Code</label>
                        <input type="text" id="addCountryCode" value="91" placeholder="91"
                            style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                        <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Without + sign</div>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">WhatsApp Number</label>
                        <input type="text" id="addPhoneNumber" placeholder="9876543210"
                            style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                        <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Digits only, no country code</div>
                    </div>
                </div>

                <div style="margin-bottom:22px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Business Display Name</label>
                    <input type="text" id="addDisplayName" placeholder="e.g. Dreams Resort Group"
                        style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                    <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Brand name shown on WhatsApp — templates use each hotel's own name as variable</div>
                </div>

                <div id="addError" style="display:none;background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;"></div>
                <div id="addSuccess" style="display:none;background:#dcfce7;color:#15803d;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;"></div>

                <div style="display:flex;gap:12px;">
                    <button onclick="closeAddModal()" style="flex:1;background:#f3f4f6;color:#374151;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
                    <button id="addSubmitBtn" onclick="submitAdd()"
                        style="flex:2;background:#25D366;color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                        Register &amp; Send OTP
                    </button>
                </div>
            </div>

            {{-- TAB: Link Existing --}}
            <div id="tabLink" style="display:none;">
                <div style="background:#f5f3ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:#6d28d9;line-height:1.6;">
                    <i class="fas fa-link"></i> &nbsp;<strong>Chain / Multi-Property:</strong> Share one verified number across multiple hotels. Each hotel's messages will still use its own name in templates — only the sender number is shared.
                </div>

                @if($activeConfigs->isEmpty())
                <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:16px;text-align:center;margin-bottom:20px;color:#92400e;font-size:13px;">
                    <i class="fas fa-exclamation-triangle" style="margin-bottom:6px;display:block;font-size:20px;"></i>
                    No verified numbers yet. Register and verify at least one hotel number first, then you can share it with other hotels.
                </div>
                @else
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Hotel to configure</label>
                    <select id="linkHotelId" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;">
                        <option value="">— Select Hotel —</option>
                        @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}">{{ $hotel->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:22px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Use number from</label>
                    <select id="linkSourceConfigId" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;">
                        <option value="">— Select existing number —</option>
                        @foreach($activeConfigs as $ac)
                        <option value="{{ $ac->id }}">
                            +{{ $ac->phone_number }} &nbsp;·&nbsp; {{ $ac->managed_display_name }} &nbsp;({{ $ac->hotel->name ?? 'Hotel #'.$ac->hotel_id }})
                        </option>
                        @endforeach
                    </select>
                    <div style="font-size:11px;color:#9ca3af;margin-top:3px;">No OTP needed — number is already verified. Templates still use each hotel's own name.</div>
                </div>
                @endif

                <div id="linkError" style="display:none;background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;"></div>
                <div id="linkSuccess" style="display:none;background:#dcfce7;color:#15803d;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;"></div>

                <div style="display:flex;gap:12px;">
                    <button onclick="closeAddModal()" style="flex:1;background:#f3f4f6;color:#374151;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
                    @if(!$activeConfigs->isEmpty())
                    <button id="linkSubmitBtn" onclick="submitLink()"
                        style="flex:2;background:#7c3aed;color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-link"></i> Link Number
                    </button>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ===== VERIFY OTP MODAL ===== --}}
<div id="verifyModal" style="display:none;position:fixed;inset:0;z-index:9000;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);" onclick="closeVerifyModal()"></div>
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
        <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:420px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.2);pointer-events:auto;">
            <button onclick="closeVerifyModal()" style="position:absolute;top:16px;right:16px;background:#f3f4f6;border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:18px;color:#6b7280;line-height:1;">×</button>

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
                <div style="width:44px;height:44px;background:#7c3aed;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-key" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:17px;font-weight:800;color:#111827;">Enter OTP</div>
                    <div id="verifyPhoneDisplay" style="font-size:13px;color:#6b7280;"></div>
                </div>
            </div>

            <div style="background:#f5f3ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:13px;color:#6d28d9;line-height:1.6;">
                <i class="fas fa-mobile-alt"></i> &nbsp;Ask the hotel owner for the <strong>6-digit OTP</strong> they received via SMS on <span id="verifyPhoneInline"></span>
            </div>

            <div style="margin-bottom:22px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">OTP Code</label>
                <input type="text" id="verifyCode" placeholder="Enter 6-digit code" maxlength="8"
                    style="width:100%;padding:12px 16px;border:2px solid #e9d5ff;border-radius:10px;font-size:22px;font-weight:700;letter-spacing:6px;text-align:center;box-sizing:border-box;"
                    onkeyup="if(event.key==='Enter') submitVerify()">
            </div>

            <div id="verifyError" style="display:none;background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;"></div>
            <div id="verifySuccess" style="display:none;background:#dcfce7;color:#15803d;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;"></div>

            <div style="display:flex;gap:12px;">
                <button onclick="closeVerifyModal()" style="flex:1;background:#f3f4f6;color:#374151;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
                <button id="verifySubmitBtn" onclick="submitVerify()"
                    style="flex:2;background:#7c3aed;color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                    Verify &amp; Activate
                </button>
            </div>
        </div>
    </div>
</div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.wa-spinner { width:16px;height:16px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;display:inline-block;vertical-align:middle;margin-right:6px; }
</style>

<script>
var _addReloadOnClose = false;
var _verifyConfigId = null;
var _verifyReloadOnClose = false;
var _activeTab = 'new';

function switchTab(tab) {
    _activeTab = tab;
    var isNew = tab === 'new';
    document.getElementById('tabNew').style.display  = isNew ? 'block' : 'none';
    document.getElementById('tabLink').style.display = isNew ? 'none'  : 'block';
    document.getElementById('tabNewBtn').style.background  = isNew ? '#fff' : 'transparent';
    document.getElementById('tabNewBtn').style.color       = isNew ? '#111827' : '#6b7280';
    document.getElementById('tabNewBtn').style.boxShadow   = isNew ? '0 1px 3px rgba(0,0,0,0.1)' : 'none';
    document.getElementById('tabLinkBtn').style.background = isNew ? 'transparent' : '#fff';
    document.getElementById('tabLinkBtn').style.color      = isNew ? '#6b7280' : '#111827';
    document.getElementById('tabLinkBtn').style.boxShadow  = isNew ? 'none' : '0 1px 3px rgba(0,0,0,0.1)';
}

function openAddModal(hotelId, hotelName) {
    switchTab('new');
    document.getElementById('addHotelId').value = hotelId || '';
    if (document.getElementById('linkHotelId')) document.getElementById('linkHotelId').value = hotelId || '';
    document.getElementById('addCountryCode').value = '91';
    document.getElementById('addPhoneNumber').value = '';
    document.getElementById('addDisplayName').value = '';
    document.getElementById('addModalHotelName').textContent = hotelName ? 'For: ' + hotelName : 'Select a hotel below';
    document.getElementById('addError').style.display = 'none';
    document.getElementById('addSuccess').style.display = 'none';
    document.getElementById('addSubmitBtn').innerHTML = 'Register &amp; Send OTP';
    document.getElementById('addSubmitBtn').disabled = false;
    _addReloadOnClose = false;
    document.getElementById('addModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    if (_addReloadOnClose) location.reload();
}

function submitAdd() {
    var hotelId     = document.getElementById('addHotelId').value;
    var cc          = document.getElementById('addCountryCode').value.trim();
    var phone       = document.getElementById('addPhoneNumber').value.trim();
    var displayName = document.getElementById('addDisplayName').value.trim();
    var errEl       = document.getElementById('addError');
    var successEl   = document.getElementById('addSuccess');

    errEl.style.display = 'none';
    successEl.style.display = 'none';

    if (!hotelId)     { errEl.textContent = 'Please select a hotel.'; errEl.style.display = 'block'; return; }
    if (!phone)       { errEl.textContent = 'Please enter the phone number.'; errEl.style.display = 'block'; return; }
    if (!displayName) { errEl.textContent = 'Please enter a display name.'; errEl.style.display = 'block'; return; }

    var btn = document.getElementById('addSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="wa-spinner"></span> Sending OTP...';

    fetch('{{ route('platform.whatsapp.numbers.register') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ hotel_id: hotelId, country_code: cc, phone_number: phone, display_name: displayName }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            successEl.textContent = data.message;
            successEl.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-check"></i> Done';
            _addReloadOnClose = true;
            _verifyConfigId = data.config_id;
        } else {
            errEl.textContent = data.error || 'Something went wrong.';
            errEl.style.display = 'block';
            btn.innerHTML = 'Register &amp; Send OTP';
            btn.disabled = false;
        }
    })
    .catch(function(e) {
        errEl.textContent = 'Network error: ' + e.message;
        errEl.style.display = 'block';
        btn.innerHTML = 'Register &amp; Send OTP';
        btn.disabled = false;
    });
}

function submitLink() {
    var hotelId  = document.getElementById('linkHotelId') ? document.getElementById('linkHotelId').value : '';
    var sourceId = document.getElementById('linkSourceConfigId') ? document.getElementById('linkSourceConfigId').value : '';
    var errEl    = document.getElementById('linkError');
    var successEl = document.getElementById('linkSuccess');

    errEl.style.display = 'none';
    successEl.style.display = 'none';

    if (!hotelId)  { errEl.textContent = 'Please select a hotel to configure.'; errEl.style.display = 'block'; return; }
    if (!sourceId) { errEl.textContent = 'Please select a number to link from.'; errEl.style.display = 'block'; return; }

    var btn = document.getElementById('linkSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="wa-spinner"></span> Linking...';

    fetch('{{ route('platform.whatsapp.numbers.link') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ hotel_id: hotelId, source_config_id: sourceId }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            successEl.textContent = data.message;
            successEl.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-check"></i> Linked!';
            _addReloadOnClose = true;
        } else {
            errEl.textContent = data.error || 'Something went wrong.';
            errEl.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-link"></i> Link Number';
            btn.disabled = false;
        }
    })
    .catch(function(e) {
        errEl.textContent = 'Network error: ' + e.message;
        errEl.style.display = 'block';
        btn.innerHTML = '<i class="fas fa-link"></i> Link Number';
        btn.disabled = false;
    });
}

function openVerifyModal(configId, phone) {
    _verifyConfigId = configId;
    _verifyReloadOnClose = false;
    document.getElementById('verifyPhoneDisplay').textContent = 'Verifying ' + phone;
    document.getElementById('verifyPhoneInline').textContent = phone;
    document.getElementById('verifyCode').value = '';
    document.getElementById('verifyError').style.display = 'none';
    document.getElementById('verifySuccess').style.display = 'none';
    document.getElementById('verifySubmitBtn').innerHTML = 'Verify &amp; Activate';
    document.getElementById('verifySubmitBtn').disabled = false;
    document.getElementById('verifyModal').style.display = 'block';
    setTimeout(function(){ document.getElementById('verifyCode').focus(); }, 100);
}

function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
    if (_verifyReloadOnClose) location.reload();
}

function submitVerify() {
    var code    = document.getElementById('verifyCode').value.trim();
    var errEl   = document.getElementById('verifyError');
    var successEl = document.getElementById('verifySuccess');

    errEl.style.display = 'none';
    successEl.style.display = 'none';

    if (!code) { errEl.textContent = 'Please enter the OTP code.'; errEl.style.display = 'block'; return; }

    var btn = document.getElementById('verifySubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="wa-spinner"></span> Verifying...';

    fetch('/platform/whatsapp/numbers/' + _verifyConfigId + '/verify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ code: code }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            successEl.textContent = data.message;
            successEl.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-check"></i> Activated!';
            _verifyReloadOnClose = true;
        } else {
            errEl.textContent = data.error || 'Verification failed.';
            errEl.style.display = 'block';
            btn.innerHTML = 'Verify &amp; Activate';
            btn.disabled = false;
        }
    })
    .catch(function(e) {
        errEl.textContent = 'Network error: ' + e.message;
        errEl.style.display = 'block';
        btn.innerHTML = 'Verify &amp; Activate';
        btn.disabled = false;
    });
}

function resendOtp(configId, btn) {
    var orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    fetch('/platform/whatsapp/numbers/' + configId + '/request-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Sent';
            btn.style.color = '#16a34a';
        } else {
            btn.innerHTML = '<i class="fas fa-times"></i> Failed';
            btn.style.color = '#dc2626';
        }
        btn.disabled = false;
    })
    .catch(function() {
        btn.innerHTML = orig;
        btn.disabled = false;
    });
}

function removeNumber(configId, hotelName) {
    if (!confirm('Remove managed number for ' + hotelName + '? The hotel will switch back to shared mode.')) return;
    fetch('/platform/whatsapp/numbers/' + configId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) location.reload();
        else alert(data.error || 'Could not remove number.');
    })
    .catch(function(e) { alert('Network error: ' + e.message); });
}
</script>
@endsection
