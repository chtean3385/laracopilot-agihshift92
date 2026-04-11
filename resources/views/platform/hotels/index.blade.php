@extends('layouts.platform')

@section('title', 'Hotels — Platform Admin')
@section('page-title', 'Hotel Management')
@section('page-subtitle', 'All tenants — create, configure, suspend')

@section('content')

@php
    $planCfg = fn($slug) => $plans[$slug] ?? ['label' => ucfirst($slug), 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569', 'monthly_price' => 0, 'yearly_price' => 0];
    $fmt     = fn($n) => $currencySymbol . ' ' . number_format($n, 0);
@endphp

{{-- Header action --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <div>
        <span id="hotelCount" style="font-size:13px;color:#64748b;">
            {{ $totalCount }} hotel{{ $totalCount !== 1 ? 's' : '' }} registered on platform
        </span>
    </div>
    <a href="{{ route('platform.hotels.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Hotel
    </a>
</div>

{{-- Search & Filter bar (live client-side filtering) --}}
<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="position:relative;flex:1;min-width:200px;">
        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none;"></i>
        <input type="text" id="hotelSearch" value="{{ $search }}" placeholder="Search by name, slug, email, phone…"
            oninput="filterHotels()"
            style="width:100%;padding:9px 14px 9px 36px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;color:#1e293b;outline:none;box-sizing:border-box;background:#fff;"
            autocomplete="off">
    </div>
    <select id="hotelStatusFilter" onchange="filterHotels()"
        style="padding:9px 32px 9px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;color:#374151;background:#fff;outline:none;cursor:pointer;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;">
        <option value="">All Statuses</option>
        <option value="active"    {{ $status === 'active'    ? 'selected' : '' }}>Active</option>
        <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
    </select>
    <select id="hotelPlanFilter" onchange="filterHotels()"
        style="padding:9px 32px 9px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;color:#374151;background:#fff;outline:none;cursor:pointer;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;">
        <option value="">All Plans</option>
        @foreach($plans as $slug => $planData)
        <option value="{{ $slug }}" {{ $planFilter === $slug ? 'selected' : '' }}>{{ $planData['label'] }}</option>
        @endforeach
    </select>
    <button type="button" onclick="clearHotelFilters()" id="hotelClearBtn"
        style="padding:9px 14px;background:#f1f5f9;color:#64748b;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;display:{{ ($search !== '' || $status !== '' || $planFilter !== '') ? 'inline-flex' : 'none' }};align-items:center;gap:5px;">
        <i class="fas fa-times"></i> Clear
    </button>
</div>

{{-- Table card --}}
<div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">

    @if($hotels->isEmpty())
    <div style="padding:80px 24px;text-align:center;">
        <i class="fas fa-{{ ($search !== '' || $status !== '' || $planFilter !== '') ? 'search' : 'building' }}" style="font-size:48px;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
        @if($search !== '' || $status !== '' || $planFilter !== '')
        <p style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 8px;">No hotels match your filters</p>
        <p style="color:#94a3b8;margin:0 0 20px;">Try different search terms or clear your filters.</p>
        <a href="{{ route('platform.hotels.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            <i class="fas fa-times"></i> Clear Filters
        </a>
        @else
        <p style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 8px;">No hotels yet</p>
        <p style="color:#94a3b8;margin:0 0 20px;">Create the first hotel tenant to get started.</p>
        <a href="{{ route('platform.hotels.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i> Create First Hotel
        </a>
        @endif
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">Hotel</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Plan</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Subscription</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Status</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Expiry</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Rooms</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Bookings</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Users</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Created</th>
                    <th style="text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hotels as $hotel)
                @php
                    $plan     = $planCfg($hotel->plan);
                    $isActive = $hotel->status === 'active';
                    $sBg      = $isActive ? '#dcfce7' : '#fee2e2';
                    $sTx      = $isActive ? '#15803d' : '#b91c1c';
                    $cycle       = $hotel->billing_cycle ?? 'monthly';
                    $effMonthly  = ($hotel->custom_monthly_price > 0) ? (float)$hotel->custom_monthly_price : ($plan['monthly_price'] ?? 0);
                    $effYearly   = ($hotel->custom_yearly_price  > 0) ? (float)$hotel->custom_yearly_price  : ($plan['yearly_price']  ?? 0);
                    $isCustom    = $hotel->custom_monthly_price > 0 || $hotel->custom_yearly_price > 0;
                    $trialEnd    = $hotel->trial_ends_at  ? \Carbon\Carbon::parse($hotel->trial_ends_at)  : null;
                    $planExp     = $hotel->plan_expires_at ? \Carbon\Carbon::parse($hotel->plan_expires_at) : null;
                    $trialExp    = $trialEnd && $trialEnd->isPast();
                    $planExpired = $planExp  && $planExp->isPast();
                @endphp
                <tr class="hotel-row"
                    data-search="{{ strtolower($hotel->name . ' ' . $hotel->slug . ' ' . $hotel->email . ' ' . $hotel->phone) }}"
                    data-status="{{ $hotel->status }}"
                    data-plan="{{ $hotel->plan }}"
                    style="border-bottom:1px solid #f8fafc;cursor:pointer;" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background='transparent'" onclick="window.location='{{ route('platform.hotels.edit', $hotel->id) }}'" title="Click to edit {{ addslashes($hotel->name) }}">

                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:linear-gradient(135deg,{{ $isActive ? '#8b5cf6,#4c1d95' : '#94a3b8,#475569' }});border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="color:#fff;font-size:13px;font-weight:800;">{{ strtoupper(substr($hotel->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ $hotel->name }}</div>
                                <div style="font-size:11px;color:#94a3b8;font-family:monospace;">{{ $hotel->slug }}</div>
                            </div>
                        </div>
                    </td>

                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $plan['badge_bg'] }};color:{{ $plan['badge_text'] }};">
                            {{ $plan['label'] }}
                        </span>
                    </td>

                    {{-- Subscription pricing --}}
                    <td style="padding:14px;">
                        @if($effMonthly > 0 || $effYearly > 0)
                        <div style="display:flex;align-items:center;gap:5px;">
                            @if($cycle === 'yearly')
                            <span style="font-size:13px;font-weight:700;color:#1e293b;">{{ $currencySymbol }} {{ number_format($effYearly) }}<span style="font-size:10px;font-weight:400;color:#94a3b8;">/yr</span></span>
                            @else
                            <span style="font-size:13px;font-weight:700;color:#1e293b;">{{ $currencySymbol }} {{ number_format($effMonthly) }}<span style="font-size:10px;font-weight:400;color:#94a3b8;">/mo</span></span>
                            @endif
                            @if($isCustom)
                            <span style="font-size:9px;font-weight:700;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;">CUSTOM</span>
                            @endif
                        </div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $cycle === 'yearly' ? 'Yearly · Rs '.number_format($effMonthly).'/mo equiv' : 'Monthly billing' }}</div>
                        @else
                        <span style="font-size:12px;color:#94a3b8;">—</span>
                        @endif
                    </td>

                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $sBg }};color:{{ $sTx }};">
                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $sTx }};display:inline-block;"></span>
                            {{ ucfirst($hotel->status) }}
                        </span>
                    </td>

                    {{-- Expiry column --}}
                    <td style="padding:14px;">
                        @if($trialEnd)
                            <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:2px;">Trial</div>
                            <div style="font-size:12px;font-weight:700;color:{{ $trialExp ? '#b91c1c' : '#d97706' }};">
                                {{ $trialEnd->format('d M Y') }}
                            </div>
                            <div style="font-size:10px;color:#94a3b8;">{{ $trialExp ? 'Expired' : $trialEnd->diffForHumans() }}</div>
                        @elseif($planExp)
                            <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:2px;">Plan</div>
                            <div style="font-size:12px;font-weight:700;color:{{ $planExpired ? '#b91c1c' : '#15803d' }};">
                                {{ $planExp->format('d M Y') }}
                            </div>
                            <div style="font-size:10px;color:#94a3b8;">{{ $planExpired ? 'Expired' : $planExp->diffForHumans() }}</div>
                        @else
                            <span style="font-size:12px;color:#94a3b8;">—</span>
                        @endif
                    </td>

                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->room_count) }}</span>
                        @if($hotel->max_rooms && $hotel->max_rooms < PHP_INT_MAX)
                        <span style="font-size:10px;color:#94a3b8;display:block;">/ {{ $hotel->max_rooms }}</span>
                        @endif
                    </td>

                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->booking_count) }}</span>
                    </td>

                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->user_count) }}</span>
                        @if($hotel->max_users && $hotel->max_users < PHP_INT_MAX)
                        <span style="font-size:10px;color:#94a3b8;display:block;">/ {{ $hotel->max_users }}</span>
                        @endif
                    </td>

                    <td style="padding:14px;">
                        <span style="font-size:12px;color:#64748b;">{{ \Carbon\Carbon::parse($hotel->created_at)->format('d M Y') }}</span>
                    </td>

                    <td style="padding:14px 20px;" onclick="event.stopPropagation()">
                        <div style="display:flex;align-items:center;justify-content:center;gap:5px;flex-wrap:wrap;">

                            {{-- Send Welcome Email --}}
                            <button type="button"
                                onclick="openWelcomeModal({{ $hotel->id }}, '{{ addslashes($hotel->name) }}')"
                                style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#ecfdf5;color:#065f46;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;"
                                title="Send welcome / onboarding email to hotel admin">
                                <i class="fas fa-envelope"></i> Send Email
                            </button>

                            {{-- Quick WhatsApp --}}
                            @if($hotel->phone)
                            <button type="button"
                                onclick="openQuickWA({{ $hotel->id }}, '{{ addslashes($hotel->name) }}', '{{ addslashes($hotel->phone) }}', {{ $hotel->owner_wa_consent ? 'true' : 'false' }})"
                                style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:{{ $hotel->owner_wa_consent ? '#dcfce7' : '#f1f5f9' }};color:{{ $hotel->owner_wa_consent ? '#15803d' : '#64748b' }};border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;"
                                title="{{ $hotel->owner_wa_consent ? 'Send WhatsApp (consented)' : 'Send WhatsApp (no consent yet)' }}">
                                <i class="fab fa-whatsapp"></i> WA
                            </button>
                            @endif

                            {{-- View CRM --}}
                            <a href="{{ route('platform.hotels.view-in-crm', $hotel->id) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#ede9fe;color:#6d28d9;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;"
                               title="Open this hotel in CRM">
                                <i class="fas fa-eye"></i> CRM
                            </a>

                            {{-- Edit --}}
                            <a href="{{ route('platform.hotels.edit', $hotel->id) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#f1f5f9;color:#475569;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;"
                               title="Edit hotel settings">
                                <i class="fas fa-cog"></i> Edit
                            </a>

                            {{-- Suspend / Activate toggle --}}
                            @if($isActive)
                            <form method="POST" action="{{ route('platform.hotels.suspend', $hotel->id) }}" style="margin:0;" onsubmit="return confirm('Suspend {{ addslashes($hotel->name) }}? All staff logins will be blocked.')">
                                @csrf
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#fee2e2;color:#b91c1c;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-ban"></i> Suspend
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('platform.hotels.activate', $hotel->id) }}" style="margin:0;">
                                @csrf
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#dcfce7;color:#15803d;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-check-circle"></i> Activate
                                </button>
                            </form>

                            {{-- Delete — only available when suspended --}}
                            <form method="POST" action="{{ route('platform.hotels.destroy', $hotel->id) }}" style="margin:0;"
                                  onsubmit="return confirm('⚠️ PERMANENT DELETE\n\nThis will hard-delete \"{{ addslashes($hotel->name) }}\" and ALL associated data:\nrooms, bookings, payments, invoices, guests, users, settings.\n\nThis CANNOT be undone. Type the hotel name to confirm.\n\nAre you absolutely sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#7f1d1d;color:#fca5a5;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;"
                                    title="Permanently delete this hotel and all its data">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>

                </tr>
                @endforeach
                <tr id="hotelNoResults" style="display:none;">
                    <td colspan="10" style="padding:60px 24px;text-align:center;">
                        <i class="fas fa-search" style="font-size:40px;color:#e2e8f0;display:block;margin-bottom:12px;"></i>
                        <p style="font-size:16px;font-weight:700;color:#1e293b;margin:0 0 6px;">No hotels match your search</p>
                        <p style="color:#94a3b8;font-size:13px;margin:0 0 16px;">Try different search terms or clear the filters.</p>
                        <button onclick="clearHotelFilters()" style="padding:8px 18px;background:#f1f5f9;color:#475569;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;"><i class="fas fa-times" style="margin-right:5px;"></i>Clear Filters</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($hotels->hasPages())
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid #f1f5f9;flex-wrap:wrap;gap:8px;">
        <span style="font-size:12px;color:#94a3b8;">
            Showing {{ $hotels->firstItem() }}–{{ $hotels->lastItem() }} of {{ $hotels->total() }} hotels
        </span>
        <div style="display:flex;align-items:center;gap:4px;">
            @if($hotels->onFirstPage())
            <span style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#cbd5e1;background:#f8fafc;">← Prev</span>
            @else
            <a href="{{ $hotels->previousPageUrl() }}" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#475569;text-decoration:none;background:#fff;">← Prev</a>
            @endif

            @foreach($hotels->getUrlRange(max(1, $hotels->currentPage()-2), min($hotels->lastPage(), $hotels->currentPage()+2)) as $page => $url)
                @if($page == $hotels->currentPage())
                <span style="padding:6px 12px;border:1px solid #7c3aed;border-radius:8px;font-size:13px;font-weight:700;color:#fff;background:#7c3aed;">{{ $page }}</span>
                @else
                <a href="{{ $url }}" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#475569;text-decoration:none;background:#fff;">{{ $page }}</a>
                @endif
            @endforeach

            @if($hotels->hasMorePages())
            <a href="{{ $hotels->nextPageUrl() }}" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#475569;text-decoration:none;background:#fff;">Next →</a>
            @else
            <span style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#cbd5e1;background:#f8fafc;">Next →</span>
            @endif
        </div>
    </div>
    @endif

    @endif

</div>

{{-- ── Welcome Email Modal ──────────────────────────────────────────────── --}}
<div id="welcomeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:460px;margin:20px;box-shadow:0 20px 60px rgba(0,0,0,.2);">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
                <h3 style="font-size:17px;font-weight:800;color:#1e293b;margin:0 0 3px;">Send Welcome Email</h3>
                <p id="welcomeModalSubtitle" style="font-size:12px;color:#94a3b8;margin:0;"></p>
            </div>
            <button onclick="closeWelcomeModal()" style="width:30px;height:30px;background:#f1f5f9;border:none;border-radius:8px;font-size:16px;cursor:pointer;color:#64748b;display:flex;align-items:center;justify-content:center;">&times;</button>
        </div>

        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 16px;margin-bottom:20px;">
            <p style="font-size:12px;color:#15803d;margin:0;line-height:1.6;">
                <strong>🎉 Onboarding Email</strong> — This will send a welcome email with login credentials and the portal link to the hotel admin.
            </p>
        </div>

        <form id="welcomeForm" method="POST" action="">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px;">Admin Name</label>
                <input type="text" name="admin_name" id="modalAdminName" required
                    style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;color:#1e293b;outline:none;box-sizing:border-box;"
                    placeholder="Admin full name">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px;">Admin Email</label>
                <input type="email" name="admin_email" id="modalAdminEmail" required
                    style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;color:#1e293b;outline:none;box-sizing:border-box;"
                    placeholder="admin@hotel.com">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px;">Password to Include in Email</label>
                <input type="text" name="admin_password" required
                    style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;color:#1e293b;outline:none;font-family:monospace;box-sizing:border-box;"
                    placeholder="Enter the admin's current password">
                <p style="font-size:11px;color:#94a3b8;margin:5px 0 0;">This password will be shown in the email body so the admin can log in.</p>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="closeWelcomeModal()"
                    style="flex:1;padding:11px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <i class="fas fa-paper-plane"></i> Send Welcome Email
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Quick WhatsApp Modal ──────────────────────────────────────────────── --}}
<div id="quickWaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:0;width:100%;max-width:480px;margin:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">
        <div style="padding:18px 22px;background:linear-gradient(135deg,#128c43,#25d366);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:16px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;">
                    <i class="fab fa-whatsapp"></i> Quick WhatsApp
                </div>
                <div id="qaModalSubtitle" style="font-size:12px;color:rgba(255,255,255,.8);margin-top:2px;"></div>
            </div>
            <button onclick="closeQuickWA()" style="width:32px;height:32px;background:rgba(255,255,255,.2);border:none;border-radius:8px;color:#fff;cursor:pointer;font-size:18px;">✕</button>
        </div>

        <div id="qaConsentWarn" style="display:none;background:#fef3c7;padding:10px 18px;font-size:12px;font-weight:600;color:#92400e;border-bottom:1px solid #fde68a;">
            ⚠️ This owner hasn't consented to WhatsApp messages yet. Proceed with caution.
        </div>

        <div style="padding:20px 22px;">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:12px;">Choose a template to send:</div>

            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px;">
                <label id="tpl-crm_update" onclick="selectWaTpl('crm_update')" style="border:2px solid #e2e8f0;border-radius:12px;padding:12px 14px;cursor:pointer;transition:border-color .15s;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                        <input type="radio" name="qa_tpl" value="crm_update" style="accent-color:#25d366;">
                        <span style="font-size:13px;font-weight:700;color:#1e293b;">📣 CRM Dashboard Update</span>
                    </div>
                    <div style="font-size:11px;color:#64748b;line-height:1.5;margin-left:22px;">
                        "Hello [Name], your Hotel CRM dashboard has recent updates… 👉 Access: [URL]"
                    </div>
                </label>

                <label id="tpl-login_reminder" onclick="selectWaTpl('login_reminder')" style="border:2px solid #e2e8f0;border-radius:12px;padding:12px 14px;cursor:pointer;transition:border-color .15s;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                        <input type="radio" name="qa_tpl" value="login_reminder" style="accent-color:#25d366;">
                        <span style="font-size:13px;font-weight:700;color:#1e293b;">🔔 Login Reminder</span>
                    </div>
                    <div style="font-size:11px;color:#64748b;line-height:1.5;margin-left:22px;">
                        "Hello [Name], we noticed you haven't logged in recently. Check your bookings: [URL]"
                    </div>
                </label>
            </div>

            <div id="qaResult" style="display:none;border-radius:10px;padding:10px 14px;font-size:13px;font-weight:600;margin-bottom:14px;"></div>

            <form id="qaForm" method="POST" action="" onsubmit="return submitQuickWA(event)">
                @csrf
                <input type="hidden" name="template_key" id="qaTemplateKey" value="">
                <button type="submit" id="qaSubmitBtn" disabled
                    style="width:100%;padding:12px;background:linear-gradient(135deg,#25d366,#128c43);color:#fff;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;opacity:.5;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="fab fa-whatsapp"></i> Send WhatsApp Now
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openWelcomeModal(hotelId, hotelName) {
    document.getElementById('welcomeModalSubtitle').textContent = hotelName;
    document.getElementById('welcomeForm').action = '/platform/hotels/' + hotelId + '/send-welcome';
    document.getElementById('modalAdminName').value = '';
    document.getElementById('modalAdminEmail').value = '';
    document.getElementById('welcomeModal').style.display = 'flex';
}
function closeWelcomeModal() {
    document.getElementById('welcomeModal').style.display = 'none';
}
document.getElementById('welcomeModal').addEventListener('click', function(e) {
    if (e.target === this) closeWelcomeModal();
});

var _qaHotelId = null;
function openQuickWA(hotelId, hotelName, phone, consented) {
    _qaHotelId = hotelId;
    document.getElementById('qaModalSubtitle').textContent = 'To: ' + hotelName + ' (' + phone + ')';
    document.getElementById('qaConsentWarn').style.display = consented ? 'none' : 'block';
    document.getElementById('qaResult').style.display = 'none';
    document.getElementById('qaTemplateKey').value = '';
    document.getElementById('qaSubmitBtn').disabled = true;
    document.getElementById('qaSubmitBtn').style.opacity = '.5';
    document.querySelectorAll('[name="qa_tpl"]').forEach(r => r.checked = false);
    document.querySelectorAll('[id^="tpl-"]').forEach(el => el.style.borderColor = '#e2e8f0');
    document.getElementById('quickWaModal').style.display = 'flex';
}
function closeQuickWA() {
    document.getElementById('quickWaModal').style.display = 'none';
}
function selectWaTpl(key) {
    document.querySelectorAll('[id^="tpl-"]').forEach(el => el.style.borderColor = '#e2e8f0');
    var lbl = document.getElementById('tpl-' + key);
    if (lbl) lbl.style.borderColor = '#25d366';
    document.getElementById('qaTemplateKey').value = key;
    document.getElementById('qaSubmitBtn').disabled = false;
    document.getElementById('qaSubmitBtn').style.opacity = '1';
}
function submitQuickWA(e) {
    e.preventDefault();
    var btn = document.getElementById('qaSubmitBtn');
    var res = document.getElementById('qaResult');
    var tpl = document.getElementById('qaTemplateKey').value;
    if (!tpl || !_qaHotelId) return false;
    btn.disabled = true; btn.style.opacity = '.5'; btn.textContent = 'Sending…';
    fetch('/platform/hotels/' + _qaHotelId + '/send-quick-wa', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({template_key: tpl})
    }).then(r => r.json()).then(data => {
        res.style.display = 'block';
        res.style.background = data.success ? '#dcfce7' : '#fee2e2';
        res.style.color      = data.success ? '#15803d' : '#b91c1c';
        res.textContent      = data.message;
        btn.disabled = false; btn.style.opacity = '1';
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send WhatsApp Now';
    }).catch(() => {
        res.style.display = 'block'; res.style.background = '#fee2e2'; res.style.color = '#b91c1c';
        res.textContent = '❌ Request failed.';
        btn.disabled = false; btn.style.opacity = '1';
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send WhatsApp Now';
    });
    return false;
}
document.getElementById('quickWaModal').addEventListener('click', function(e) {
    if (e.target === this) closeQuickWA();
});

// ── Live hotel filtering ──────────────────────────────────────────────────
var _totalHotels = document.querySelectorAll('.hotel-row').length;

function filterHotels() {
    var term   = (document.getElementById('hotelSearch').value || '').toLowerCase().trim();
    var status = (document.getElementById('hotelStatusFilter').value || '').toLowerCase();
    var plan   = (document.getElementById('hotelPlanFilter').value || '').toLowerCase();
    var rows   = document.querySelectorAll('.hotel-row');
    var shown  = 0;

    rows.forEach(function(row) {
        var search = row.dataset.search || '';
        var rowStatus = row.dataset.status || '';
        var rowPlan   = row.dataset.plan   || '';
        var visible = true;
        if (term   && search.indexOf(term) === -1)   visible = false;
        if (status && rowStatus !== status)           visible = false;
        if (plan   && rowPlan   !== plan)             visible = false;
        row.style.display = visible ? '' : 'none';
        if (visible) shown++;
    });

    var noRes = document.getElementById('hotelNoResults');
    if (noRes) noRes.style.display = (shown === 0) ? '' : 'none';

    var countEl = document.getElementById('hotelCount');
    if (countEl) {
        var hasFilter = term || status || plan;
        if (!hasFilter) {
            countEl.textContent = _totalHotels + ' hotel' + (_totalHotels !== 1 ? 's' : '') + ' registered on platform';
        } else {
            countEl.textContent = shown + ' of ' + _totalHotels + ' hotel' + (_totalHotels !== 1 ? 's' : '') + ' shown';
        }
    }

    var clearBtn = document.getElementById('hotelClearBtn');
    if (clearBtn) clearBtn.style.display = (term || status || plan) ? 'inline-flex' : 'none';
}

function clearHotelFilters() {
    document.getElementById('hotelSearch').value = '';
    document.getElementById('hotelStatusFilter').value = '';
    document.getElementById('hotelPlanFilter').value = '';
    filterHotels();
}

// Run on page load to apply any pre-filled search from URL
document.addEventListener('DOMContentLoaded', filterHotels);
</script>
@endpush

@endsection
