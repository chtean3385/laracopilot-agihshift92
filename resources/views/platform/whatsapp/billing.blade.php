@extends('layouts.platform')

@section('title', 'WhatsApp Billing')
@section('page-title', 'WhatsApp Billing')
@section('page-subtitle', 'Message usage & billing per hotel — ₹0.0086 / message')

@section('content')
@if(session('success'))
<div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px 18px;border-radius:10px;margin-bottom:18px;font-size:13px;font-weight:600;">
    <i class="fas fa-check-circle" style="margin-right:6px;"></i>{{ session('success') }}
</div>
@endif

{{-- ── Header + Month Picker ──────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
    <div>
        <div style="font-size:22px;font-weight:900;color:#1e293b;">{{ $periodLabel }}</div>
        <div style="font-size:12px;color:#94a3b8;">{{ $periodStart->format('d M') }} – {{ $periodEnd->format('d M Y') }}</div>
    </div>
    <form method="GET" action="{{ route('platform.whatsapp.billing') }}" style="display:flex;align-items:center;gap:8px;">
        <select name="month" onchange="this.form.submit()"
            style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;background:#fff;color:#1e293b;font-weight:600;cursor:pointer;">
            @foreach($availableMonths as $m)
            <option value="{{ $m }}" {{ $m === $selectedMonth ? 'selected' : '' }}>
                {{ \Carbon\Carbon::parse($m.'-01')->format('F Y') }}
            </option>
            @endforeach
        </select>
    </form>
</div>

{{-- ── Summary Cards ──────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:26px;">
    @php
    $cards = [
        ['label'=>'Total Messages',  'value'=> number_format($totalMessages),      'color'=>'#6d28d9', 'bg'=>'#f5f3ff', 'icon'=>'fa-comments'],
        ['label'=>'Total Amount',    'value'=> '₹'.number_format($totalAmount,2),  'color'=>'#1d4ed8', 'bg'=>'#eff6ff', 'icon'=>'fa-rupee-sign'],
        ['label'=>'Collected',       'value'=> '₹'.number_format($totalPaid,2),    'color'=>'#059669', 'bg'=>'#ecfdf5', 'icon'=>'fa-check-circle'],
        ['label'=>'Outstanding',     'value'=> '₹'.number_format($totalUnpaid,2),  'color'=>'#dc2626', 'bg'=>'#fef2f2', 'icon'=>'fa-clock'],
    ];
    @endphp
    @foreach($cards as $c)
    <div style="background:{{ $c['bg'] }};border:1.5px solid {{ $c['color'] }}22;border-radius:14px;padding:16px 18px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <i class="fas {{ $c['icon'] }}" style="color:{{ $c['color'] }};font-size:14px;"></i>
            <span style="font-size:11px;font-weight:700;color:{{ $c['color'] }};text-transform:uppercase;letter-spacing:.5px;">{{ $c['label'] }}</span>
        </div>
        <div style="font-size:22px;font-weight:900;color:{{ $c['color'] }};">{{ $c['value'] }}</div>
    </div>
    @endforeach
</div>

{{-- ── Per-Hotel Table ─────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:1.5px solid #e2e8f0;">
                <th style="padding:12px 16px;text-align:left;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">Hotel</th>
                <th style="padding:12px 10px;text-align:center;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">Today</th>
                <th style="padding:12px 10px;text-align:center;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">This Week</th>
                <th style="padding:12px 10px;text-align:center;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">{{ $periodLabel }}</th>
                <th style="padding:12px 10px;text-align:right;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">Amount (₹)</th>
                <th style="padding:12px 10px;text-align:center;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">Status</th>
                <th style="padding:12px 10px;text-align:center;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">Daily Limit</th>
                <th style="padding:12px 10px;text-align:center;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">Monthly Limit</th>
                <th style="padding:12px 16px;text-align:center;font-weight:800;color:#64748b;font-size:11px;text-transform:uppercase;">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($hotels as $hotel)
        @php
            $monthly = $monthlyCounts[$hotel->id] ?? 0;
            $today   = $todayCounts[$hotel->id]   ?? 0;
            $week    = $weekCounts[$hotel->id]     ?? 0;
            $amount  = round($monthly * 0.0086, 2);
            $cycle   = $cycles[$hotel->id] ?? null;
            $isPaid  = $cycle && $cycle->status === 'paid';
            $dlimit  = $hotel->wa_daily_limit;
            $mlimit  = $hotel->wa_monthly_limit;
            $dlimitHit = $dlimit && $today  >= $dlimit;
            $mlimitHit = $mlimit && $monthly >= $mlimit;
        @endphp
        <tr style="border-bottom:1px solid #f1f5f9;" class="billing-row">
            <td style="padding:14px 16px;">
                <div style="font-weight:700;color:#1e293b;">{{ $hotel->name }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $hotel->plan ?? '—' }}
                    @if($isPaid)
                    &nbsp;·&nbsp;<span style="color:#059669;font-weight:700;">Paid {{ $cycle->paid_at?->format('d M') }}</span>
                    @endif
                </div>
            </td>
            <td style="padding:14px 10px;text-align:center;">
                <span style="{{ $dlimitHit ? 'color:#dc2626;font-weight:800;' : 'color:#475569;font-weight:600;' }}">{{ number_format($today) }}</span>
                @if($dlimit)<div style="font-size:10px;color:{{ $dlimitHit ? '#dc2626' : '#94a3b8' }};">/ {{ number_format($dlimit) }}</div>@endif
            </td>
            <td style="padding:14px 10px;text-align:center;color:#475569;font-weight:600;">{{ number_format($week) }}</td>
            <td style="padding:14px 10px;text-align:center;">
                <span style="{{ $mlimitHit ? 'color:#dc2626;font-weight:800;' : 'color:#1e293b;font-weight:700;' }}">{{ number_format($monthly) }}</span>
                @if($mlimit)<div style="font-size:10px;color:{{ $mlimitHit ? '#dc2626' : '#94a3b8' }};">/ {{ number_format($mlimit) }}</div>@endif
            </td>
            <td style="padding:14px 10px;text-align:right;font-weight:800;color:#1e293b;">
                ₹{{ number_format($amount, 2) }}
            </td>
            <td style="padding:14px 10px;text-align:center;">
                @if($isPaid)
                <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:4px 12px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:4px;">
                    <i class="fas fa-check-circle"></i> Paid
                </span>
                @elseif($monthly > 0)
                <span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:4px 12px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:4px;">
                    <i class="fas fa-clock"></i> Unpaid
                </span>
                @else
                <span style="color:#cbd5e1;font-size:11px;">No messages</span>
                @endif
            </td>
            {{-- Daily Limit ─────────────────────────────────────────── --}}
            <td style="padding:10px;" id="dlimit-cell-{{ $hotel->id }}">
                <div style="display:flex;align-items:center;gap:4px;justify-content:center;">
                    <span id="dlimit-display-{{ $hotel->id }}" style="font-size:12px;color:#475569;font-weight:600;cursor:pointer;" onclick="editLimit({{ $hotel->id }}, 'daily')">
                        {{ $dlimit ? number_format($dlimit) : '∞' }}
                    </span>
                    <button onclick="editLimit({{ $hotel->id }}, 'daily')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:2px 4px;" title="Edit limit">
                        <i class="fas fa-pencil-alt" style="font-size:10px;"></i>
                    </button>
                </div>
            </td>
            {{-- Monthly Limit ───────────────────────────────────────── --}}
            <td style="padding:10px;" id="mlimit-cell-{{ $hotel->id }}">
                <div style="display:flex;align-items:center;gap:4px;justify-content:center;">
                    <span id="mlimit-display-{{ $hotel->id }}" style="font-size:12px;color:#475569;font-weight:600;cursor:pointer;" onclick="editLimit({{ $hotel->id }}, 'monthly')">
                        {{ $mlimit ? number_format($mlimit) : '∞' }}
                    </span>
                    <button onclick="editLimit({{ $hotel->id }}, 'monthly')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:2px 4px;" title="Edit limit">
                        <i class="fas fa-pencil-alt" style="font-size:10px;"></i>
                    </button>
                </div>
            </td>
            {{-- Actions ─────────────────────────────────────────────── --}}
            <td style="padding:10px;text-align:center;">
                @if($monthly > 0)
                    @if(!$isPaid)
                    <button onclick="openPayModal({{ $hotel->id }}, '{{ addslashes($hotel->name) }}', {{ $monthly }}, '{{ $amount }}')"
                        style="padding:6px 14px;background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-check"></i> Mark Paid
                    </button>
                    @else
                    <form method="POST" action="{{ route('platform.whatsapp.billing.unpaid', $hotel->id) }}" style="display:inline;" onsubmit="return confirm('Revert to unpaid?')">
                        @csrf @method('POST')
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <button type="submit" style="padding:6px 10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:8px;font-size:11px;cursor:pointer;white-space:nowrap;">
                            Revert
                        </button>
                    </form>
                    @endif
                @else
                <span style="color:#cbd5e1;font-size:11px;">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="padding:40px;text-align:center;color:#94a3b8;">No hotels found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:12px;font-size:11px;color:#94a3b8;">
    Rate: ₹0.0086 per outbound message (0.86 paise). Counts from successful outgoing WhatsApp messages only.
</div>

{{-- ── Mark Paid Modal ─────────────────────────────────────────────────── --}}
<div id="payModal" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#059669,#047857);padding:18px 22px;display:flex;align-items:center;gap:12px;">
            <i class="fas fa-check-circle" style="color:#fff;font-size:20px;"></i>
            <div>
                <div style="font-weight:800;color:#fff;font-size:15px;">Mark as Paid</div>
                <div id="payModalSub" style="color:#a7f3d0;font-size:12px;"></div>
            </div>
        </div>
        <form id="payModalForm" method="POST" style="padding:22px;">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <div style="background:#f0fdf4;border:1.5px solid #6ee7b7;border-radius:12px;padding:14px;margin-bottom:16px;">
                <div id="payModalDetail" style="font-size:13px;color:#065f46;line-height:1.8;"></div>
            </div>
            <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">Notes (optional)</label>
            <textarea name="notes" rows="2" placeholder="e.g. Paid via bank transfer, ref #1234"
                style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;resize:none;box-sizing:border-box;"></textarea>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="submit" style="flex:1;padding:12px;background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;">
                    <i class="fas fa-check"></i> Confirm Paid
                </button>
                <button type="button" onclick="document.getElementById('payModal').style.display='none'"
                    style="padding:12px 16px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Limit Edit Modal ────────────────────────────────────────────────── --}}
<div id="limitModal" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#6d28d9,#4c1d95);padding:18px 22px;display:flex;align-items:center;gap:12px;">
            <i class="fas fa-sliders-h" style="color:#fff;font-size:20px;"></i>
            <div>
                <div style="font-weight:800;color:#fff;font-size:15px;">Set Message Limit</div>
                <div id="limitModalSub" style="color:#ddd6fe;font-size:12px;"></div>
            </div>
        </div>
        <form id="limitModalForm" method="POST" style="padding:22px;">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">Daily Limit</label>
                    <input type="number" name="wa_daily_limit" id="limitModalDaily" min="0" placeholder="∞ Unlimited"
                        style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;box-sizing:border-box;">
                    <div style="font-size:10px;color:#94a3b8;margin-top:4px;">Leave blank = no limit</div>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">Monthly Limit</label>
                    <input type="number" name="wa_monthly_limit" id="limitModalMonthly" min="0" placeholder="∞ Unlimited"
                        style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;box-sizing:border-box;">
                    <div style="font-size:10px;color:#94a3b8;margin-top:4px;">Leave blank = no limit</div>
                </div>
            </div>
            <div style="background:#faf5ff;border:1px solid #ddd6fe;border-radius:10px;padding:10px 12px;margin-bottom:16px;font-size:12px;color:#6d28d9;">
                <i class="fas fa-info-circle"></i>
                When the limit is reached, WhatsApp messages will be silently blocked until the next day / month or you increase the limit.
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" style="flex:1;padding:12px;background:linear-gradient(135deg,#6d28d9,#4c1d95);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;">
                    Save Limits
                </button>
                <button type="button" onclick="document.getElementById('limitModal').style.display='none'"
                    style="padding:12px 16px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayModal(hotelId, hotelName, count, amount) {
    document.getElementById('payModalSub').textContent   = hotelName;
    document.getElementById('payModalDetail').innerHTML  =
        '<b>' + count.toLocaleString() + ' messages</b> sent in this period<br>' +
        'Amount due: <b>₹' + parseFloat(amount).toFixed(2) + '</b>';
    document.getElementById('payModalForm').action =
        '/platform/whatsapp/billing/' + hotelId + '/mark-paid';
    document.getElementById('payModal').style.display = 'flex';
}

function editLimit(hotelId, type) {
    document.getElementById('limitModalSub').textContent = '';
    document.getElementById('limitModalForm').action =
        '/platform/whatsapp/billing/' + hotelId + '/limit';
    document.getElementById('payModal').style.display = 'none';
    // Fetch current values from the DOM
    var dVal = document.getElementById('dlimit-display-' + hotelId).textContent.trim();
    var mVal = document.getElementById('mlimit-display-' + hotelId).textContent.trim();
    document.getElementById('limitModalDaily').value   = dVal === '∞' ? '' : dVal.replace(/,/g,'');
    document.getElementById('limitModalMonthly').value = mVal === '∞' ? '' : mVal.replace(/,/g,'');
    document.getElementById('limitModal').style.display = 'flex';
}

document.getElementById('payModal').addEventListener('click', function(e){if(e.target===this)this.style.display='none';});
document.getElementById('limitModal').addEventListener('click', function(e){if(e.target===this)this.style.display='none';});
</script>
@endsection
