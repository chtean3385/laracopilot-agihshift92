@extends('layouts.admin')
@section('title', $customer->name)
@section('page-title', 'Guest Profile')
@section('page-subtitle', 'Full profile and booking history')

@section('content')
@php
    $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
    $ci = crc32($customer->name) % 5; if($ci<0) $ci+=5;
    $avatarGrad = $gradients[$ci];
    $totalNights = $customer->bookings->sum('nights');
    $totalSpent  = $customer->bookings->sum('total_amount');
    $lastBooking = $customer->bookings->sortByDesc('check_in_date')->first();
@endphp
<style>
.info-row { display:flex; align-items:flex-start; gap:12px; padding:12px 14px; background:#f8fafc; border-radius:12px; transition:background .15s; }
.info-row:hover { background:#f1f5f9; }
.info-icon { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.bk-row { display:flex; align-items:center; gap:16px; padding:14px 20px; border-top:1px solid #f8fafc; transition:background .12s; }
.bk-row:hover { background:#f8fafc; }
</style>

<div style="display:flex;flex-direction:column;gap:24px;">

    {{-- Back + Action buttons --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <a href="{{ route('customers.index') }}" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;color:#475569;text-decoration:none;transition:all .15s;" onmouseenter="this.style.borderColor='#94a3b8'" onmouseleave="this.style.borderColor='#e2e8f0'">
            <i class="fas fa-arrow-left" style="font-size:11px;"></i> Back to Guests
        </a>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('documents.index', $customer->id) }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;color:#475569;text-decoration:none;transition:all .15s;" onmouseenter="this.style.borderColor='#94a3b8'" onmouseleave="this.style.borderColor='#e2e8f0'">
                <i class="fas fa-file-alt" style="font-size:11px;"></i> Documents
                <span style="background:#f1f5f9;color:#64748b;font-size:11px;padding:1px 8px;border-radius:20px;">{{ $customer->documents->count() }}</span>
            </a>
            @if($customer->phone)
            <button onclick="document.getElementById('whatsapp-modal').classList.remove('hidden')" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(22,163,74,.25);transition:all .15s;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform='translateY(0)'">
                <i class="fab fa-whatsapp" style="font-size:15px;"></i> WhatsApp
            </button>
            @endif
            @canDo('guests.edit')
            <a href="{{ route('customers.edit', $customer->id) }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 3px 10px rgba(245,158,11,.25);transition:all .15s;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform='translateY(0)'">
                <i class="fas fa-edit" style="font-size:11px;"></i> Edit Profile
            </a>
            @endCanDo
            @canDo('guests.delete')
            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Permanently delete this guest?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#fff;border:1.5px solid #fecaca;border-radius:12px;font-size:13px;font-weight:600;color:#e11d48;cursor:pointer;transition:all .15s;" onmouseenter="this.style.background='#fff1f2'" onmouseleave="this.style.background='#fff'">
                    <i class="fas fa-trash" style="font-size:11px;"></i> Delete
                </button>
            </form>
            @endCanDo
        </div>
    </div>

    {{-- Hero Banner --}}
    <div style="border-radius:24px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 60%,#0e7490 100%);padding:36px 32px;display:flex;align-items:center;gap:28px;flex-wrap:wrap;">
            {{-- Avatar --}}
            <div style="position:relative;flex-shrink:0;">
                <div style="width:90px;height:90px;background:{{ $avatarGrad }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:36px;box-shadow:0 0 0 4px rgba(255,255,255,.15),0 8px 24px rgba(0,0,0,.3);">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <div style="position:absolute;bottom:2px;right:2px;width:20px;height:20px;background:#22c55e;border-radius:50%;border:3px solid #1e3a5f;"></div>
            </div>
            {{-- Name & details --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:26px;font-weight:900;color:#fff;line-height:1.1;margin-bottom:6px;">{{ $customer->name }}</div>
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                    @if($customer->phone)
                    <span style="display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.7);">
                        <i class="fas fa-phone" style="font-size:11px;"></i> {{ $customer->phone }}
                    </span>
                    @endif
                    @if($customer->email)
                    <span style="display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.7);">
                        <i class="fas fa-envelope" style="font-size:11px;"></i> {{ $customer->email }}
                    </span>
                    @endif
                    @if($customer->nationality)
                    <span style="display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.7);">
                        <i class="fas fa-globe" style="font-size:11px;"></i> {{ $customer->nationality }}
                    </span>
                    @endif
                </div>
            </div>
            {{-- Stat pills --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <div style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:14px 22px;text-align:center;min-width:90px;">
                    <div style="font-size:24px;font-weight:900;color:#fff;">{{ $customer->bookings->count() }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:2px;font-weight:600;">Total Stays</div>
                </div>
                <div style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:14px 22px;text-align:center;min-width:90px;">
                    <div style="font-size:24px;font-weight:900;color:#fff;">{{ $totalNights }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:2px;font-weight:600;">Nights</div>
                </div>
                @canDo('reports.view')
                <div style="background:linear-gradient(135deg,rgba(6,182,212,.3),rgba(59,130,246,.3));backdrop-filter:blur(8px);border:1px solid rgba(6,182,212,.4);border-radius:16px;padding:14px 22px;text-align:center;min-width:110px;">
                    <div style="font-size:20px;font-weight:900;color:#fff;">₹{{ number_format($totalSpent) }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.7);margin-top:2px;font-weight:600;">Total Spent</div>
                </div>
                @endCanDo
            </div>
        </div>
    </div>

    {{-- Main content: Profile + Bookings --}}
    <div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start;" class="guest-grid">

        {{-- Left: Profile details --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Contact Info --}}
            <div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
                <div style="font-weight:800;color:#1e293b;font-size:14px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-address-card" style="color:#06b6d4;"></i> Contact & Identity
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @if($customer->phone)
                    <div class="info-row">
                        <div class="info-icon" style="background:#ecfeff;"><i class="fas fa-phone" style="color:#0891b2;font-size:12px;"></i></div>
                        <div>
                            <div style="font-size:11px;color:#94a3b8;font-weight:600;">Phone</div>
                            <div style="font-size:13px;color:#1e293b;font-weight:600;">{{ $customer->phone }}</div>
                        </div>
                    </div>
                    @endif
                    @if($customer->email)
                    <div class="info-row">
                        <div class="info-icon" style="background:#eff6ff;"><i class="fas fa-envelope" style="color:#2563eb;font-size:12px;"></i></div>
                        <div>
                            <div style="font-size:11px;color:#94a3b8;font-weight:600;">Email</div>
                            <div style="font-size:13px;color:#1e293b;font-weight:600;">{{ $customer->email }}</div>
                        </div>
                    </div>
                    @endif
                    @if($customer->age)
                    <div class="info-row">
                        <div class="info-icon" style="background:#fdf4ff;"><i class="fas fa-user-clock" style="color:#9333ea;font-size:12px;"></i></div>
                        <div>
                            <div style="font-size:11px;color:#94a3b8;font-weight:600;">Age</div>
                            <div style="font-size:13px;color:#1e293b;font-weight:600;">{{ $customer->age }} yrs</div>
                        </div>
                    </div>
                    @endif
                    <div class="info-row">
                        <div class="info-icon" style="background:#fef3c7;"><i class="fas fa-id-card" style="color:#d97706;font-size:12px;"></i></div>
                        <div>
                            <div style="font-size:11px;color:#94a3b8;font-weight:600;">{{ ucwords(str_replace('_', ' ', $customer->id_type)) }}</div>
                            <div style="font-size:13px;color:#1e293b;font-weight:600;">{{ $customer->id_number }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon" style="background:#f0fdf4;"><i class="fas fa-map-marker-alt" style="color:#16a34a;font-size:12px;"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:11px;color:#94a3b8;font-weight:600;">Location</div>
                            <div style="font-size:13px;color:#1e293b;font-weight:600;">{{ implode(', ', array_filter([$customer->city, $customer->state, $customer->country])) }}</div>
                        </div>
                    </div>
                    @if($customer->address)
                    <div class="info-row">
                        <div class="info-icon" style="background:#f8fafc;"><i class="fas fa-home" style="color:#64748b;font-size:12px;"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:11px;color:#94a3b8;font-weight:600;">Address</div>
                            <div style="font-size:13px;color:#1e293b;">{{ $customer->address }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            @if($customer->notes)
            <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-radius:16px;padding:18px;border:1px solid #fde68a;">
                <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:6px;display:flex;align-items:center;gap:6px;"><i class="fas fa-sticky-note"></i> Notes</div>
                <p style="font-size:13px;color:#78350f;line-height:1.5;margin:0;">{{ $customer->notes }}</p>
            </div>
            @endif

            {{-- Last stay --}}
            @if($lastBooking)
            <div style="background:linear-gradient(135deg,#ecfeff,#e0f2fe);border-radius:16px;padding:18px;border:1px solid #a5f3fc;">
                <div style="font-size:12px;font-weight:700;color:#0e7490;margin-bottom:10px;display:flex;align-items:center;gap:6px;"><i class="fas fa-history"></i> Last Stay</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">Room {{ $lastBooking->room->room_number }}</div>
                <div style="font-size:12px;color:#0891b2;margin-top:2px;">{{ $lastBooking->check_in_date->format('d M Y') }} → {{ $lastBooking->check_out_date->format('d M Y') }}</div>
                <div style="margin-top:8px;">
                    <span style="display:inline-flex;align-items:center;padding:3px 10px;background:rgba(255,255,255,.7);border-radius:20px;font-size:11px;font-weight:700;color:#0e7490;">{{ ucfirst(str_replace('_', ' ', $lastBooking->status)) }}</span>
                </div>
            </div>
            @endif

        </div>

        {{-- Right: Booking History --}}
        <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
            <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:11px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-calendar-check" style="color:#fff;font-size:13px;"></i>
                    </div>
                    <div>
                        <div style="font-weight:800;color:#1e293b;font-size:15px;">Booking History</div>
                        <div style="font-size:11px;color:#94a3b8;">{{ $customer->bookings->count() }} booking(s) on record</div>
                    </div>
                </div>
                @canDo('bookings.create')
                <a href="{{ route('bookings.create') }}?customer_id={{ $customer->id }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;border-radius:11px;font-size:12px;font-weight:700;text-decoration:none;box-shadow:0 3px 8px rgba(6,182,212,.25);">
                    <i class="fas fa-plus"></i> New Booking
                </a>
                @endCanDo
            </div>

            @if($customer->bookings->count() > 0)
            @foreach($customer->bookings->sortByDesc('check_in_date') as $booking)
            @php
                $statusColors = ['confirmed'=>'#dbeafe|#1d4ed8','checked_in'=>'#dcfce7|#15803d','checked_out'=>'#f3f4f6|#374151','cancelled'=>'#fee2e2|#b91c1c','pending'=>'#fef3c7|#92400e'];
                $sc = $statusColors[$booking->status] ?? '#f3f4f6|#374151';
                [$scBg,$scText] = explode('|',$sc);
            @endphp
            <div class="bk-row">
                {{-- Room bubble --}}
                <div style="width:50px;height:50px;background:linear-gradient(135deg,#0f172a,#1e3a5f);border-radius:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">
                    <div style="font-size:10px;color:rgba(255,255,255,.5);font-weight:600;line-height:1;">ROOM</div>
                    <div style="font-size:16px;font-weight:900;color:#fff;line-height:1.1;">{{ $booking->room->room_number }}</div>
                </div>
                {{-- Booking # and dates --}}
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                        <span style="font-family:monospace;font-size:13px;font-weight:700;color:#0891b2;">{{ $booking->booking_number }}</span>
                        <span style="display:inline-flex;align-items:center;padding:2px 9px;background:{{ $scBg }};color:{{ $scText }};border-radius:20px;font-size:10px;font-weight:700;">
                            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                        </span>
                    </div>
                    <div style="font-size:12px;color:#64748b;">
                        <i class="far fa-calendar" style="margin-right:4px;"></i>
                        {{ $booking->check_in_date->format('d M Y') }} &rarr; {{ $booking->check_out_date->format('d M Y') }}
                        <span style="color:#94a3b8;margin-left:6px;">· {{ $booking->nights }} night(s)</span>
                    </div>
                </div>
                {{-- Amount --}}
                @canDo('reports.view')
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-weight:800;color:#1e293b;font-size:15px;">₹{{ number_format($booking->total_amount) }}</div>
                    @if($booking->balance_due > 0)
                    <div style="font-size:11px;color:#e11d48;font-weight:600;">₹{{ number_format($booking->balance_due) }} due</div>
                    @else
                    <div style="font-size:11px;color:#16a34a;font-weight:600;">Settled</div>
                    @endif
                </div>
                @endCanDo
                {{-- View link --}}
                <a href="{{ route('bookings.show', $booking->id) }}" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb;border-radius:10px;text-decoration:none;flex-shrink:0;transition:background .12s;" onmouseenter="this.style.background='#dbeafe'" onmouseleave="this.style.background='#eff6ff'">
                    <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                </a>
            </div>
            @endforeach
            @else
            <div style="padding:60px 24px;text-align:center;color:#94a3b8;">
                <div style="width:64px;height:64px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-calendar-times" style="font-size:24px;color:#cbd5e1;"></i>
                </div>
                <div style="font-size:15px;font-weight:700;color:#475569;margin-bottom:6px;">No bookings yet</div>
                @canDo('bookings.create')
                <a href="{{ route('bookings.create') }}?customer_id={{ $customer->id }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;margin-top:4px;">
                    <i class="fas fa-plus"></i> Create First Booking
                </a>
                @endCanDo
            </div>
            @endif
        </div>

    </div>

</div>

{{-- Mobile responsive --}}
<style>
@media (max-width: 1024px) { .guest-grid { grid-template-columns: 1fr !important; } }
</style>

{{-- WhatsApp Modal --}}
@if($customer->phone)
<div id="whatsapp-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.55);">
    <div style="background:#fff;border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:480px;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(22,163,74,.3);">
                    <i class="fab fa-whatsapp" style="color:#fff;font-size:20px;"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#1e293b;font-size:15px;">Send WhatsApp</div>
                    <div style="font-size:12px;color:#64748b;">{{ $customer->name }} · {{ $customer->phone }}</div>
                </div>
            </div>
            <button onclick="document.getElementById('whatsapp-modal').classList.add('hidden')" style="width:32px;height:32px;background:#f1f5f9;border:none;border-radius:9px;cursor:pointer;color:#64748b;font-size:16px;display:flex;align-items:center;justify-content:center;">×</button>
        </div>
        <div style="padding:24px;">
            <div style="margin-bottom:16px;">
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Message Template</label>
                <select id="wa-template" onchange="applyTemplate()" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;background:#fff;">
                    <option value="">Select a template…</option>
                    <option value="booking_reminder">Booking Reminder</option>
                    <option value="checkin_details">Check-In Details</option>
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="checkout_reminder">Check-Out Reminder</option>
                    <option value="custom">Custom Message</option>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Message</label>
                <textarea id="wa-message" rows="5" style="width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;" placeholder="Type your message…">Dear {{ $customer->name }}, </textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <a id="wa-send-btn" href="#" target="_blank" onclick="openWhatsApp(event)" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-weight:700;font-size:14px;padding:12px 20px;border-radius:14px;text-decoration:none;box-shadow:0 4px 12px rgba(22,163,74,.3);">
                    <i class="fab fa-whatsapp" style="font-size:16px;"></i>Open in WhatsApp
                </a>
                <button onclick="document.getElementById('whatsapp-modal').classList.add('hidden')" style="padding:12px 20px;background:#f1f5f9;border:none;border-radius:14px;font-size:14px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
const customerName = "{{ $customer->name }}";
const resortName   = "{{ $settings->resort_name ?? 'our resort' }}";
const phone        = "{{ preg_replace('/\D/', '', $customer->phone) }}";
const templates = {
    booking_reminder : `Dear ${customerName}, this is a friendly reminder from ${resortName}. Your booking is confirmed. Please don't hesitate to contact us if you need any assistance before your arrival. We look forward to welcoming you!`,
    checkin_details  : `Dear ${customerName}, your room is ready at ${resortName}! Please arrive at our reception with a valid photo ID. Check-in time is 2:00 PM. We're excited to host you!`,
    payment_reminder : `Dear ${customerName}, we noticed there is an outstanding balance on your booking at ${resortName}. Kindly settle the amount at your earliest convenience. Thank you!`,
    checkout_reminder: `Dear ${customerName}, this is a reminder that your check-out at ${resortName} is scheduled for tomorrow. Check-out time is 11:00 AM. We hope you had a wonderful stay!`,
    custom           : `Dear ${customerName}, `,
};
function applyTemplate() {
    const key = document.getElementById('wa-template').value;
    if (key && templates[key]) document.getElementById('wa-message').value = templates[key];
}
function openWhatsApp(e) {
    e.preventDefault();
    const msg = document.getElementById('wa-message').value.trim();
    if (!msg) { alert('Please enter a message before sending.'); return; }
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(msg)}`, '_blank');
}
</script>
@endif

@endsection
