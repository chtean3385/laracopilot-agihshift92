@extends('layouts.platform')

@section('title', 'Edit Hotel — Platform Admin')
@section('page-title', 'Edit Hotel')
@section('page-subtitle')
{{ $hotel->name }} — change settings, plan or status
@endsection

@section('content')

<div style="display:flex;gap:28px;align-items:flex-start;">

{{-- ═══ LEFT COLUMN ═══ --}}
<div style="flex:1;min-width:0;max-width:720px;">

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
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Phone <span style="font-size:10px;font-weight:500;color:#94a3b8;">(Owner WhatsApp — include country code, e.g. 919725XXXXXX)</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $hotel->phone) }}"
                        style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('phone')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;">
                    @if(isset($errors) && $errors->has('phone')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('phone') }}</p> @endif

                    {{-- WhatsApp consent toggle --}}
                    <label style="display:inline-flex;align-items:center;gap:8px;margin-top:10px;cursor:pointer;user-select:none;">
                        <input type="checkbox" name="owner_wa_consent" value="1" {{ old('owner_wa_consent', $hotel->owner_wa_consent ?? false) ? 'checked' : '' }}
                            style="width:16px;height:16px;accent-color:#25d366;cursor:pointer;">
                        <span style="font-size:12px;font-weight:700;color:#374151;">
                            <i class="fab fa-whatsapp" style="color:#25d366;margin-right:4px;"></i>
                            Owner has consented to receive WhatsApp messages from Dreams Technology
                        </span>
                    </label>
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

            @php
                $hotelOnTrial   = ($hotel->plan === 'trial' || $hotel->plan === null);
                $trialEndsAt    = $hotel->trial_ends_at ? \Carbon\Carbon::parse($hotel->trial_ends_at) : null;
                $effectivePlan  = old('plan', $hotelOnTrial ? array_key_first($plans) : $hotel->plan);
            @endphp

            @if($hotelOnTrial)
            <div style="display:flex;align-items:center;gap:10px;background:#fef3c7;border:1.5px solid #fcd34d;border-radius:12px;padding:12px 16px;margin-bottom:16px;">
                <i class="fas fa-clock" style="color:#d97706;font-size:15px;"></i>
                <div>
                    <div style="font-size:13px;font-weight:700;color:#92400e;">Trial Active
                        @if($trialEndsAt) — ends {{ $trialEndsAt->format('d M Y') }} ({{ $trialEndsAt->diffForHumans() }}) @endif
                    </div>
                    <div style="font-size:11px;color:#b45309;margin-top:2px;">Select the plan that will activate when the trial ends.</div>
                </div>
            </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
                @foreach($plans as $slug => $plan)
                @php $isCurrent = $effectivePlan === $slug; @endphp
                <label style="cursor:pointer;">
                    <input type="radio" name="plan" value="{{ $slug }}" {{ $isCurrent ? 'checked' : '' }} style="position:absolute;opacity:0;" class="plan-radio" data-max-rooms="{{ $plan['max_rooms'] == PHP_INT_MAX ? 999 : $plan['max_rooms'] }}" data-max-users="{{ $plan['max_users'] == PHP_INT_MAX ? 999 : $plan['max_users'] }}">
                    <div class="plan-card" data-plan="{{ $slug }}" style="border:2px solid {{ $isCurrent ? $plan['color'] : '#e2e8f0' }};border-radius:14px;padding:16px;transition:border-color .2s;background:{{ $isCurrent ? 'rgba(139,92,246,.04)' : '#fff' }};">
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

            {{-- Billing Cycle --}}
            @php $currentCycle = old('billing_cycle', $hotel->billing_cycle ?? 'monthly'); @endphp
            <div style="border-top:1px solid #f1f5f9;padding-top:20px;margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;">Billing Cycle <span style="color:#ef4444;">*</span></label>
                <div style="display:flex;gap:12px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 18px;border:2px solid {{ $currentCycle === 'monthly' ? '#7c3aed' : '#e2e8f0' }};border-radius:10px;flex:1;" id="cycle-monthly-label">
                        <input type="radio" name="billing_cycle" value="monthly" {{ $currentCycle === 'monthly' ? 'checked' : '' }} onchange="updateCycleBorder()" style="accent-color:#7c3aed;">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1e293b;">Monthly</div>
                            <div style="font-size:11px;color:#64748b;">Billed each month</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 18px;border:2px solid {{ $currentCycle === 'yearly' ? '#7c3aed' : '#e2e8f0' }};border-radius:10px;flex:1;" id="cycle-yearly-label">
                        <input type="radio" name="billing_cycle" value="yearly" {{ $currentCycle === 'yearly' ? 'checked' : '' }} onchange="updateCycleBorder()" style="accent-color:#7c3aed;">
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
                        <input type="number" name="custom_monthly_price" id="custom_monthly_price" value="{{ old('custom_monthly_price', $hotel->custom_monthly_price ?? '') }}" min="0"
                            placeholder="e.g. 499"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;box-sizing:border-box;outline:none;">
                        <div id="plan_monthly_hint" style="font-size:10px;color:#94a3b8;margin-top:4px;"></div>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Custom Yearly Price (Rs)</label>
                        <input type="number" name="custom_yearly_price" id="custom_yearly_price" value="{{ old('custom_yearly_price', $hotel->custom_yearly_price ?? '') }}" min="0"
                            placeholder="e.g. 4999"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;box-sizing:border-box;outline:none;">
                        <div id="plan_yearly_hint" style="font-size:10px;color:#94a3b8;margin-top:4px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hotel Admin Account --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 18px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#10b981,#059669);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-user-shield" style="color:#fff;font-size:12px;"></i>
                </span>
                Hotel Admin Account
            </h2>

            {{-- Current Admin Card --}}
            @if($hotelAdmin)
            <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;margin-bottom:16px;">
                <div style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px;flex-shrink:0;">{{ strtoupper(substr($hotelAdmin->name, 0, 1)) }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:800;color:#1e293b;">{{ $hotelAdmin->name }}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $hotelAdmin->email }}</div>
                    <div style="margin-top:5px;display:flex;align-items:center;gap:6px;">
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:#dcfce7;color:#15803d;">
                            <i class="fas fa-shield-halved" style="font-size:9px;"></i> Hotel Admin
                        </span>
                        @if($hotelAdmin->status === 'active')
                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:#dcfce7;color:#15803d;">Active</span>
                        @else
                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:#fee2e2;color:#b91c1c;">{{ ucfirst($hotelAdmin->status) }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('platform.users.reset.show', $hotelAdmin->id) }}"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#ede9fe;color:#7c3aed;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;"
                    onmouseover="this.style.background='#ddd6fe'" onmouseout="this.style.background='#ede9fe'">
                    <i class="fas fa-key"></i> Reset Password
                </a>
            </div>
            @else
            <div style="padding:14px 16px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <i class="fas fa-exclamation-triangle" style="color:#d97706;font-size:14px;flex-shrink:0;"></i>
                <span style="font-size:13px;color:#92400e;">No hotel admin currently assigned.</span>
            </div>
            @endif

            {{-- Reassign Admin --}}
            @if($hotelUsers->count() > 0)
            <div style="border-top:1px solid #f1f5f9;padding-top:16px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">
                    <i class="fas fa-user-pen" style="color:#7c3aed;margin-right:4px;"></i>
                    Reassign Admin
                </label>
                <select name="new_admin_user_id" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;color:#1e293b;background:#fff;outline:none;cursor:pointer;">
                    <option value="">— Keep current admin —</option>
                    @foreach($hotelUsers as $u)
                    @php $isCurrent = $hotelAdmin && $hotelAdmin->id === $u->id; @endphp
                    <option value="{{ $u->id }}" {{ $isCurrent ? 'disabled' : '' }} style="{{ $isCurrent ? 'color:#94a3b8' : '' }}">
                        {{ $u->name }} ({{ $u->email }}){{ $isCurrent ? ' — current admin' : '' }}
                    </option>
                    @endforeach
                </select>
                <p style="font-size:11px;color:#94a3b8;margin:6px 0 0;">Select a user from this hotel's active staff to promote to Hotel Admin. Save the form to apply.</p>
            </div>
            @else
            <p style="font-size:11px;color:#94a3b8;margin:0;">No other active hotel users available for reassignment. Add users via the CRM Users section first.</p>
            @endif
        </div>

        {{-- Status --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#0891b2,#0e7490);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
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

        {{-- Backup Settings --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:24px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 6px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#0ea5e9,#0284c7);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-database" style="color:#fff;font-size:12px;"></i>
                </span>
                Automatic Backup
            </h2>
            <p style="font-size:12px;color:#94a3b8;margin:0 0 18px;">Silently backs up hotel data on a schedule. Hotel staff cannot see this.</p>

            <label style="display:flex;align-items:center;gap:12px;cursor:pointer;padding:14px 18px;border:2px solid {{ old('backup_auto_enabled', $backupSetting->auto_backup_enabled) ? '#0ea5e9' : '#e2e8f0' }};border-radius:12px;margin-bottom:16px;transition:border-color .2s;" id="backup-toggle-label">
                <input type="hidden" name="backup_auto_enabled" value="0">
                <input type="checkbox" name="backup_auto_enabled" value="1"
                    {{ old('backup_auto_enabled', $backupSetting->auto_backup_enabled) ? 'checked' : '' }}
                    onchange="document.getElementById('backup-toggle-label').style.borderColor=this.checked?'#0ea5e9':'#e2e8f0'"
                    style="width:18px;height:18px;accent-color:#0ea5e9;cursor:pointer;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1e293b;">Enable Auto-Backup</div>
                    <div style="font-size:11px;color:#64748b;">Automatically backs up hotel data on the selected schedule</div>
                </div>
            </label>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">Backup Frequency</label>
                <div style="display:flex;gap:10px;">
                    @foreach(['24' => 'Daily', '168' => 'Weekly', '720' => 'Monthly'] as $hours => $label)
                    @php $checked = (string)old('backup_interval', $backupSetting->interval_hours) === (string)$hours; @endphp
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:9px 16px;border:2px solid {{ $checked ? '#0ea5e9' : '#e2e8f0' }};border-radius:10px;flex:1;font-size:13px;font-weight:600;color:#1e293b;">
                        <input type="radio" name="backup_interval" value="{{ $hours }}" {{ $checked ? 'checked' : '' }} style="accent-color:#0ea5e9;">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>
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

    {{-- Trial & Plan Expiry Management (outside main form so sub-forms work correctly) --}}
    @php
        $isPlanTrial  = $hotel->plan === 'trial';
        $trialEnd     = $hotel->trial_ends_at  ? \Carbon\Carbon::parse($hotel->trial_ends_at)  : null;
        $planEnd      = $hotel->plan_expires_at ? \Carbon\Carbon::parse($hotel->plan_expires_at) : null;
        $trialExpired = $trialEnd && $trialEnd->isPast();
        $planExpired  = $planEnd  && $planEnd->isPast();
    @endphp
    <div style="margin-top:20px;background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-hourglass-half" style="color:#fff;font-size:12px;"></i>
            </span>
            Trial & Plan Expiry
        </h2>

        {{-- Current dates display --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
            <div style="padding:14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;">TRIAL ENDS</div>
                <div style="font-size:14px;font-weight:800;color:{{ $trialExpired ? '#b91c1c' : ($trialEnd ? '#15803d' : '#94a3b8') }};">
                    {{ $trialEnd ? $trialEnd->format('d M Y') : '—' }}
                    @if($trialExpired) <span style="font-size:11px;font-weight:600;color:#b91c1c;">(Expired)</span> @endif
                    @if($trialEnd && !$trialExpired) <span style="font-size:11px;font-weight:500;color:#64748b;">({{ $trialEnd->diffForHumans() }})</span> @endif
                </div>
            </div>
            <div style="padding:14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;">PLAN EXPIRES</div>
                <div style="font-size:14px;font-weight:800;color:{{ $planExpired ? '#b91c1c' : ($planEnd ? '#15803d' : '#94a3b8') }};">
                    {{ $planEnd ? $planEnd->format('d M Y') : '—' }}
                    @if($planExpired) <span style="font-size:11px;font-weight:600;color:#b91c1c;">(Expired)</span> @endif
                    @if($planEnd && !$planExpired) <span style="font-size:11px;font-weight:500;color:#64748b;">({{ $planEnd->diffForHumans() }})</span> @endif
                </div>
            </div>
        </div>

        {{-- Row 1: Activate Trial + Cancel Trial --}}
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;padding-bottom:12px;border-bottom:1px dashed #f1f5f9;">

            {{-- Activate / Reset Trial --}}
            <form method="POST" action="{{ route('platform.hotels.activate-trial', $hotel->id) }}" style="margin:0;display:flex;gap:8px;align-items:flex-end;">
                @csrf
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Trial Days</label>
                    <input type="number" name="trial_days" value="7" min="1" max="90"
                           style="width:80px;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;box-sizing:border-box;">
                </div>
                <button type="submit"
                    style="padding:9px 18px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fas fa-hourglass-start"></i> Activate Trial
                </button>
            </form>

            {{-- Cancel Trial (only shown when hotel is currently on trial) --}}
            @if($hotel->plan === 'trial' || $trialEnd)
            <form method="POST" action="{{ route('platform.hotels.cancel-trial', $hotel->id) }}" style="margin:0;display:flex;gap:8px;align-items:flex-end;"
                  onsubmit="return confirm('Cancel trial for {{ addslashes($hotel->name) }}? The trial date will be cleared and the plan will revert to the selected plan.')">
                @csrf
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Revert to Plan</label>
                    <select name="revert_plan" style="padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;background:#fff;">
                        @foreach($plans as $slug => $plan)
                        <option value="{{ $slug }}" {{ $slug === 'basic' ? 'selected' : '' }}>{{ $plan['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    style="padding:9px 16px;background:#fee2e2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fas fa-times-circle"></i> Cancel Trial
                </button>
            </form>
            @endif

        </div>

        {{-- Row 2: Extend Plan + Cancel Plan Expiry --}}
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">

            {{-- Extend Plan --}}
            <form method="POST" action="{{ route('platform.hotels.extend-plan', $hotel->id) }}" style="margin:0;display:flex;gap:8px;align-items:flex-end;">
                @csrf
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Extend By (Days)</label>
                    <input type="number" name="extend_days" value="30" min="1" max="365"
                           style="width:100px;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;box-sizing:border-box;">
                </div>
                <button type="submit"
                    style="padding:9px 18px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fas fa-calendar-plus"></i> Extend Plan
                </button>
            </form>

            {{-- Cancel Plan Expiry (only shown when plan_expires_at is set) --}}
            @if($planEnd)
            <form method="POST" action="{{ route('platform.hotels.cancel-plan-expiry', $hotel->id) }}" style="margin:0;"
                  onsubmit="return confirm('Clear the plan expiry date for {{ addslashes($hotel->name) }}? The plan will have no expiry limit.')">
                @csrf
                <div style="padding-top:18px;">
                    <button type="submit"
                        style="padding:9px 16px;background:#fee2e2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                        <i class="fas fa-calendar-times"></i> Cancel Plan Expiry
                    </button>
                </div>
            </form>
            @endif

        </div>

        <p style="font-size:11px;color:#94a3b8;margin:14px 0 0;">
            <i class="fas fa-info-circle" style="margin-right:4px;"></i>
            <strong>Activate Trial</strong> — sets plan to trial, resets trial_ends_at (overrides any previous trial).
            <strong>Cancel Trial</strong> — clears trial_ends_at and reverts to selected plan.
            <strong>Extend Plan</strong> — adds days to plan_expires_at.
            <strong>Cancel Plan Expiry</strong> — removes the expiry date (no limit).
        </p>
    </div>

    {{-- Create New User for Hotel (separate form outside main form) --}}
    <div style="margin-top:28px;background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 6px;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-user-plus" style="color:#fff;font-size:12px;"></i>
            </span>
            Add New User to This Hotel
        </h2>
        <p style="font-size:12px;color:#64748b;margin:0 0 20px;">Create a new staff account and assign them directly to {{ $hotel->name }}.</p>

        @if(session('success') && str_contains(session('success'), 'created and added'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#15803d;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('platform.hotels.users.store', $hotel->id) }}">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Full Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="user_name" value="{{ old('user_name') }}" required
                        style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="e.g. Priya Sharma">
                    @if(isset($errors) && $errors->has('user_name')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('user_name') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Email <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="user_email" value="{{ old('user_email') }}" required
                        style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="staff@hotel.com">
                    @if(isset($errors) && $errors->has('user_email')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('user_email') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Password <span style="color:#ef4444;">*</span></label>
                    <input type="password" name="user_password" required autocomplete="new-password"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="Min 6 characters">
                    @if(isset($errors) && $errors->has('user_password')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('user_password') }}</p> @endif
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Role <span style="color:#ef4444;">*</span></label>
                    <select name="user_role" required
                        style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;background:#fff;outline:none;cursor:pointer;">
                        <option value="Admin" {{ old('user_role') === 'Admin' ? 'selected' : '' }}>Admin</option>
                        <option value="Manager" {{ old('user_role') === 'Manager' ? 'selected' : '' }}>Manager</option>
                        <option value="Receptionist" {{ old('user_role','Receptionist') === 'Receptionist' ? 'selected' : '' }}>Receptionist</option>
                    </select>
                </div>

                <div style="grid-column:1/-1;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 14px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;">
                        <input type="checkbox" name="make_admin" value="1" {{ old('make_admin') ? 'checked' : '' }} style="accent-color:#10b981;width:15px;height:15px;">
                        <div>
                            <span style="font-size:13px;font-weight:700;color:#15803d;">Set as Hotel Admin</span>
                            <span style="font-size:11px;color:#64748b;display:block;">This user will become the Hotel Admin (replaces current admin if any)</span>
                        </div>
                    </label>
                </div>
            </div>

            <div style="margin-top:16px;">
                <button type="submit"
                    style="padding:10px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-user-plus"></i> Create & Add User
                </button>
            </div>
        </form>
    </div>

</div>{{-- end left column --}}

{{-- ═══ RIGHT COLUMN ═══ --}}
<div style="width:360px;flex-shrink:0;position:sticky;top:80px;">

    {{-- Other Hotels with Same Admin --}}
    <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;">
        <h2 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
            <span style="width:26px;height:26px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-hotel" style="color:#fff;font-size:11px;"></i>
            </span>
            Hotels with Same Admin
        </h2>
        @if($hotelAdmin)
        <p style="font-size:11px;color:#64748b;margin:0 0 14px;">Other hotels managed by <strong>{{ $hotelAdmin->name }}</strong></p>
        @else
        <p style="font-size:11px;color:#94a3b8;margin:0 0 14px;">No admin assigned to this hotel yet.</p>
        @endif

        @if($relatedHotels->isNotEmpty())
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($relatedHotels as $rh)
            @php
                $rhBg   = $rh->status === 'active' ? '#f0fdf4' : '#fef2f2';
                $rhBdr  = $rh->status === 'active' ? '#bbf7d0' : '#fecaca';
                $rhDot  = $rh->status === 'active' ? '#16a34a' : '#dc2626';
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;background:{{ $rhBg }};border:1px solid {{ $rhBdr }};border-radius:10px;">
                <div style="min-width:0;">
                    <div style="font-size:13px;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $rh->name }}</div>
                    <div style="display:flex;align-items:center;gap:5px;margin-top:2px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $rhDot }};display:inline-block;flex-shrink:0;"></span>
                        <span style="font-size:10px;color:#64748b;text-transform:capitalize;">{{ $rh->status }}</span>
                        <span style="font-size:10px;color:#94a3b8;">· {{ strtoupper($rh->plan) }}</span>
                    </div>
                </div>
                <a href="{{ route('platform.hotels.edit', $rh->id) }}"
                   style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#ede9fe;color:#7c3aed;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;"
                   onmouseover="this.style.background='#ddd6fe'" onmouseout="this.style.background='#ede9fe'">
                    <i class="fas fa-pen-to-square" style="font-size:10px;"></i> Edit
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align:center;padding:16px 0;color:#94a3b8;font-size:12px;">
            <i class="fas fa-building" style="font-size:20px;margin-bottom:6px;display:block;opacity:.4;"></i>
            No other hotels linked to this admin yet.
        </div>
        @endif
    </div>

    {{-- Add Another Hotel (same admin) --}}
    @if($hotelAdmin)
    <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1.5px solid #ede9fe;">
        <h2 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 4px;display:flex;align-items:center;gap:8px;">
            <span style="width:26px;height:26px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-plus" style="color:#fff;font-size:11px;"></i>
            </span>
            Add Another Hotel
        </h2>
        <p style="font-size:11px;color:#6d28d9;margin:0 0 16px;padding:8px 10px;background:#f5f3ff;border-radius:8px;border-left:3px solid #7c3aed;">
            <i class="fas fa-link" style="margin-right:4px;"></i>
            <strong>{{ $hotelAdmin->name }}</strong> will be automatically linked as Admin — no password needed.
        </p>

        @if(session('success') && str_contains(session('success'), 'created and fully provisioned'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#15803d;display:flex;align-items:flex-start;gap:8px;">
            <i class="fas fa-check-circle" style="margin-top:1px;flex-shrink:0;"></i> {{ session('success') }}
        </div>
        @endif

        @if($errors->has('new_hotel_name') || $errors->has('new_hotel_plan') || $errors->has('new_hotel_billing_cycle'))
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#b91c1c;">
            <i class="fas fa-exclamation-circle"></i>
            {{ $errors->first('new_hotel_name') ?: ($errors->first('new_hotel_plan') ?: $errors->first('new_hotel_billing_cycle')) }}
        </div>
        @endif

        <form method="POST" action="{{ route('platform.hotels.add-related', $hotel->id) }}" style="margin:0;">
            @csrf

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Hotel Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="new_hotel_name" value="{{ old('new_hotel_name') }}" required
                    style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('new_hotel_name') ? '#ef4444' : '#e2e8f0' }};border-radius:9px;font-size:13px;color:#1e293b;box-sizing:border-box;outline:none;"
                    placeholder="e.g. Grand Resort Surat">
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Plan <span style="color:#ef4444;">*</span></label>
                <select name="new_hotel_plan" required
                    style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('new_hotel_plan') ? '#ef4444' : '#e2e8f0' }};border-radius:9px;font-size:13px;color:#1e293b;background:#fff;outline:none;cursor:pointer;">
                    @foreach($plans as $slug => $plan)
                    @if($slug !== 'trial')
                    <option value="{{ $slug }}" {{ old('new_hotel_plan', 'basic') === $slug ? 'selected' : '' }}>
                        {{ $plan['label'] }}@if(isset($plan['monthly_price'])) — Rs {{ number_format($plan['monthly_price']) }}/mo @endif
                    </option>
                    @endif
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Billing Cycle <span style="color:#ef4444;">*</span></label>
                <div style="display:flex;gap:8px;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:7px 12px;border:1.5px solid {{ old('new_hotel_billing_cycle','monthly')==='monthly' ? '#7c3aed' : '#e2e8f0' }};border-radius:8px;flex:1;font-size:12px;font-weight:600;color:#1e293b;">
                        <input type="radio" name="new_hotel_billing_cycle" value="monthly" {{ old('new_hotel_billing_cycle','monthly')==='monthly' ? 'checked' : '' }} style="accent-color:#7c3aed;"> Monthly
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:7px 12px;border:1.5px solid {{ old('new_hotel_billing_cycle','monthly')==='yearly' ? '#7c3aed' : '#e2e8f0' }};border-radius:8px;flex:1;font-size:12px;font-weight:600;color:#1e293b;">
                        <input type="radio" name="new_hotel_billing_cycle" value="yearly" {{ old('new_hotel_billing_cycle','monthly')==='yearly' ? 'checked' : '' }} style="accent-color:#7c3aed;"> Yearly
                    </label>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:#64748b;margin-bottom:4px;">Trial Days <span style="font-weight:400;">(opt)</span></label>
                    <input type="number" name="new_hotel_trial_days" value="{{ old('new_hotel_trial_days') }}" min="1" max="90"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="e.g. 7">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:#64748b;margin-bottom:4px;">Expires In Days <span style="font-weight:400;">(opt)</span></label>
                    <input type="number" name="new_hotel_expires_days" value="{{ old('new_hotel_expires_days') }}" min="1" max="730"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;color:#1e293b;box-sizing:border-box;outline:none;"
                        placeholder="e.g. 365">
                </div>
            </div>

            <button type="submit"
                style="width:100%;padding:10px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;"
                onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <i class="fas fa-plus-circle"></i> Create Hotel & Link Admin
            </button>
        </form>
    </div>
    @else
    <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:20px;padding:20px;">
        <p style="font-size:12px;color:#92400e;margin:0;text-align:center;">
            <i class="fas fa-exclamation-triangle" style="display:block;font-size:20px;margin-bottom:8px;"></i>
            Assign a Hotel Admin first before adding another hotel with the same admin.
        </p>
    </div>
    @endif

</div>{{-- end right column --}}

</div>{{-- end flex wrapper --}}

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
        updatePriceHints(this.value);
    });
});

var defaultPlan = document.querySelector('.plan-radio:checked');
if (defaultPlan) updatePriceHints(defaultPlan.value);
</script>
@endpush

@endsection
