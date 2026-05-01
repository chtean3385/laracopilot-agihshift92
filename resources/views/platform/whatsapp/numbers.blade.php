@extends('layouts.platform')
@section('title', 'Manage Hotel WhatsApp Numbers')

@section('content')
<div style="max-width:900px;margin:0 auto;" x-data="managedNumbers()">

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">Hotel WhatsApp Numbers</h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">Register each hotel's own WhatsApp number under your business account. One WABA, all hotels.</p>
    </div>
    @if($platform && $platform->saas_token && $platform->saas_waba_id)
    <button @click="openAdd()" style="background:#25D366;color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;">
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
    @php $cfg = $configs->get($hotel->id); @endphp
    <div style="padding:18px 24px;border-bottom:1px solid #f9fafb;display:flex;align-items:center;gap:16px;" x-data="{}">
        {{-- Hotel info --}}
        <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:700;color:#111827;">{{ $hotel->name }}</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">ID #{{ $hotel->id }}
                @if($cfg && $cfg->phone_number)
                &nbsp;·&nbsp; +{{ $cfg->phone_number }}
                @endif
                @if($cfg && $cfg->managed_display_name)
                &nbsp;·&nbsp; {{ $cfg->managed_display_name }}
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
                <span style="background:#fef3c7;color:#92400e;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;display:flex;align-items:center;gap:5px;">
                    <i class="fas fa-clock" style="font-size:9px;"></i> OTP Pending
                </span>
            @elseif($cfg->is_active && $cfg->setup_completed)
                <span style="background:#dcfce7;color:#15803d;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;display:flex;align-items:center;gap:5px;">
                    <i class="fas fa-check-circle" style="font-size:10px;"></i> Active
                </span>
            @else
                <span style="background:#f3f4f6;color:#6b7280;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">
                    Inactive
                </span>
            @endif
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:8px;flex-shrink:0;">
            @if(!$cfg || $cfg->mode !== 'managed')
                @if($platform && $platform->saas_token && $platform->saas_waba_id)
                <button @click="openAdd({{ $hotel->id }}, '{{ addslashes($hotel->name) }}')"
                    style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-plus"></i> Add Number
                </button>
                @endif
            @elseif($cfg->managed_otp_status === 'pending')
                <button @click="openVerify({{ $cfg->id }}, '+{{ $cfg->phone_number }}')"
                    style="background:#7c3aed;color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-key"></i> Enter OTP
                </button>
                <button @click="resendOtp({{ $cfg->id }}, $el)"
                    style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-redo"></i> Resend
                </button>
            @elseif($cfg->is_active)
                <button @click="openVerify({{ $cfg->id }}, '+{{ $cfg->phone_number }}')"
                    style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-key"></i> Re-verify
                </button>
            @endif

            @if($cfg && $cfg->mode === 'managed')
            <button @click="removeNumber({{ $cfg->id }}, '{{ addslashes($hotel->name) }}')"
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
<div x-show="showAdd" x-cloak style="position:fixed;inset:0;z-index:9000;display:flex;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" @click="closeAdd()"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:500px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.2);z-index:1;">
        <button @click="closeAdd()" style="position:absolute;top:16px;right:16px;background:#f3f4f6;border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:16px;color:#6b7280;">×</button>

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:44px;height:44px;background:#25D366;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fab fa-whatsapp" style="color:#fff;font-size:22px;"></i>
            </div>
            <div>
                <div style="font-size:17px;font-weight:800;color:#111827;">Add Hotel Number</div>
                <div style="font-size:13px;color:#6b7280;" x-text="addHotelName ? 'For: ' + addHotelName : 'Select a hotel below'"></div>
            </div>
        </div>

        <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:#92400e;line-height:1.6;">
            <i class="fas fa-info-circle"></i> &nbsp;Meta will send an <strong>OTP via SMS</strong> to the hotel's WhatsApp number. The hotel owner needs to share that code with you to complete verification.
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Hotel</label>
            <select x-model="addForm.hotel_id" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;">
                <option value="">— Select Hotel —</option>
                @foreach($hotels as $hotel)
                <option value="{{ $hotel->id }}">{{ $hotel->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:16px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Country Code</label>
                <input type="text" x-model="addForm.country_code" placeholder="91"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Without + sign</div>
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">WhatsApp Number</label>
                <input type="text" x-model="addForm.phone_number" placeholder="9876543210"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Digits only, no country code</div>
            </div>
        </div>

        <div style="margin-bottom:22px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Business Display Name</label>
            <input type="text" x-model="addForm.display_name" placeholder="e.g. Azure Paradise Resort"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:3px;">This name appears on WhatsApp messages sent from this number</div>
        </div>

        <div x-show="addError" x-text="addError" style="background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none;" x-cloak></div>
        <div x-show="addSuccess" x-text="addSuccess" style="background:#dcfce7;color:#15803d;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none;" x-cloak></div>

        <div style="display:flex;gap:12px;">
            <button @click="closeAdd()" style="flex:1;background:#f3f4f6;color:#374151;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
            <button @click="submitAdd()" :disabled="addLoading"
                style="flex:2;background:#25D366;color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <span x-show="addLoading" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;display:inline-block;"></span>
                <span x-text="addLoading ? 'Sending OTP...' : 'Register & Send OTP'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ===== VERIFY OTP MODAL ===== --}}
<div x-show="showVerify" x-cloak style="position:fixed;inset:0;z-index:9000;display:flex;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" @click="closeVerify()"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:420px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.2);z-index:1;">
        <button @click="closeVerify()" style="position:absolute;top:16px;right:16px;background:#f3f4f6;border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:16px;color:#6b7280;">×</button>

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:44px;height:44px;background:#7c3aed;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-key" style="color:#fff;font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size:17px;font-weight:800;color:#111827;">Enter OTP</div>
                <div style="font-size:13px;color:#6b7280;">Verifying <span x-text="verifyPhone"></span></div>
            </div>
        </div>

        <div style="background:#f5f3ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:13px;color:#6d28d9;line-height:1.6;">
            <i class="fas fa-mobile-alt"></i> &nbsp;Ask the hotel owner for the <strong>6-digit OTP</strong> they received via SMS on <span x-text="verifyPhone"></span>
        </div>

        <div style="margin-bottom:22px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">OTP Code</label>
            <input type="text" x-model="verifyCode" placeholder="Enter 6-digit code" maxlength="8"
                style="width:100%;padding:12px 16px;border:2px solid #e9d5ff;border-radius:10px;font-size:22px;font-weight:700;letter-spacing:6px;text-align:center;box-sizing:border-box;"
                @keyup.enter="submitVerify()">
        </div>

        <div x-show="verifyError" x-text="verifyError" style="background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none;" x-cloak></div>
        <div x-show="verifySuccess" x-text="verifySuccess" style="background:#dcfce7;color:#15803d;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none;" x-cloak></div>

        <div style="display:flex;gap:12px;">
            <button @click="closeVerify()" style="flex:1;background:#f3f4f6;color:#374151;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
            <button @click="submitVerify()" :disabled="verifyLoading"
                style="flex:2;background:#7c3aed;color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <span x-show="verifyLoading" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;display:inline-block;"></span>
                <span x-text="verifyLoading ? 'Verifying...' : 'Verify & Activate'"></span>
            </button>
        </div>
    </div>
</div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
[x-cloak] { display: none !important; }
</style>

<script>
function managedNumbers() {
    return {
        showAdd: false,
        addHotelName: '',
        addForm: { hotel_id: '', country_code: '91', phone_number: '', display_name: '' },
        addLoading: false,
        addError: '',
        addSuccess: '',

        showVerify: false,
        verifyConfigId: null,
        verifyPhone: '',
        verifyCode: '',
        verifyLoading: false,
        verifyError: '',
        verifySuccess: '',

        openAdd(hotelId = '', hotelName = '') {
            this.addForm = { hotel_id: hotelId || '', country_code: '91', phone_number: '', display_name: '' };
            this.addHotelName = hotelName;
            this.addError = '';
            this.addSuccess = '';
            this.showAdd = true;
        },
        closeAdd() {
            if (this.addSuccess) location.reload();
            this.showAdd = false;
        },
        async submitAdd() {
            this.addError = '';
            this.addSuccess = '';
            if (!this.addForm.hotel_id) { this.addError = 'Please select a hotel.'; return; }
            if (!this.addForm.phone_number) { this.addError = 'Please enter the phone number.'; return; }
            if (!this.addForm.display_name) { this.addError = 'Please enter a display name.'; return; }
            this.addLoading = true;
            try {
                const resp = await fetch('{{ route('platform.whatsapp.numbers.register') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.addForm),
                });
                const data = await resp.json();
                if (data.success) {
                    this.addSuccess = data.message;
                    this.verifyConfigId = data.config_id;
                } else {
                    this.addError = data.error || 'Something went wrong.';
                }
            } catch(e) {
                this.addError = 'Network error: ' + e.message;
            }
            this.addLoading = false;
        },

        openVerify(configId, phone) {
            this.verifyConfigId = configId;
            this.verifyPhone = phone;
            this.verifyCode = '';
            this.verifyError = '';
            this.verifySuccess = '';
            this.showVerify = true;
        },
        closeVerify() {
            if (this.verifySuccess) location.reload();
            this.showVerify = false;
        },
        async submitVerify() {
            this.verifyError = '';
            this.verifySuccess = '';
            if (!this.verifyCode) { this.verifyError = 'Please enter the OTP code.'; return; }
            this.verifyLoading = true;
            try {
                const resp = await fetch(`/platform/whatsapp/numbers/${this.verifyConfigId}/verify`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ code: this.verifyCode }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.verifySuccess = data.message;
                } else {
                    this.verifyError = data.error || 'Verification failed.';
                }
            } catch(e) {
                this.verifyError = 'Network error: ' + e.message;
            }
            this.verifyLoading = false;
        },

        async resendOtp(configId, btn) {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            try {
                const resp = await fetch(`/platform/whatsapp/numbers/${configId}/request-otp`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                const data = await resp.json();
                btn.innerHTML = data.success ? '<i class="fas fa-check"></i> Sent' : '<i class="fas fa-times"></i> Failed';
                if (data.success) btn.style.color = '#16a34a';
                else btn.style.color = '#dc2626';
            } catch(e) {
                btn.innerHTML = orig;
            }
            btn.disabled = false;
        },

        async removeNumber(configId, hotelName) {
            if (!confirm(`Remove managed number for ${hotelName}? The hotel will switch back to shared mode.`)) return;
            try {
                const resp = await fetch(`/platform/whatsapp/numbers/${configId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                const data = await resp.json();
                if (data.success) location.reload();
                else alert(data.error || 'Could not remove number.');
            } catch(e) {
                alert('Network error: ' + e.message);
            }
        },
    };
}
</script>
@endsection
