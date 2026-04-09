@extends('layouts.platform')
@section('title', 'Send Campaign')

@section('content')

<div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="{{ route('platform.analytics.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;color:#374151;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">
        <i class="fas fa-arrow-left"></i> Back to Analytics
    </a>
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 3px;">
            <i class="fas fa-bullhorn" style="color:#7c3aed;margin-right:8px;"></i>Engagement Campaigns
        </h1>
        <p style="color:#6b7280;font-size:13px;margin:0;">Send email or WhatsApp messages to hotel admins in bulk.</p>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:11px;padding:13px 16px;margin-bottom:18px;color:#15803d;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle" style="margin-right:7px;"></i>{{ session('success') }}
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

{{-- ─── Campaign Composer ──────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
    <div style="font-size:15px;font-weight:800;color:#1e293b;margin-bottom:18px;"><i class="fas fa-edit" style="color:#7c3aed;margin-right:8px;"></i>New Campaign</div>

    <form action="{{ route('platform.analytics.campaigns.send') }}" method="POST">
        @csrf

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Template / Key</label>
            <input type="text" name="template_key" value="{{ old('template_key', 'platform_outreach') }}"
                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;"
                placeholder="e.g. inactivity_nudge, feature_announcement">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Email Subject</label>
            <input type="text" name="subject" value="{{ old('subject') }}"
                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;"
                placeholder="Subject line for email">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Message Body</label>
            <textarea name="body" rows="7"
                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;resize:vertical;"
                placeholder="Write your message here...">{{ old('body') }}</textarea>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Channel</label>
            <select name="channel" style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;">
                <option value="email">Email only</option>
                <option value="whatsapp">WhatsApp only</option>
                <option value="both">Email + WhatsApp</option>
            </select>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">Target Hotels</label>
            <div style="display:flex;gap:8px;margin-bottom:8px;">
                <button type="button" onclick="toggleAllHotels(true)" style="padding:5px 12px;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:7px;font-size:12px;cursor:pointer;">Select All</button>
                <button type="button" onclick="toggleAllHotels(false)" style="padding:5px 12px;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:7px;font-size:12px;cursor:pointer;">Clear All</button>
                <span style="font-size:11px;color:#94a3b8;margin-top:6px;">(Leave empty = all active hotels)</span>
            </div>
            <div style="max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:9px;padding:8px;">
                @foreach($hotels as $hotel)
                <label style="display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:7px;cursor:pointer;font-size:13px;color:#374151;" class="hotel-check-row"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <input type="checkbox" name="hotel_ids[]" value="{{ $hotel->id }}" class="hotel-checkbox"
                        style="width:14px;height:14px;accent-color:#7c3aed;">
                    <span>{{ $hotel->name }}</span>
                    <span style="font-size:11px;color:#94a3b8;margin-left:auto;">{{ strtoupper($hotel->plan) }} · {{ ucfirst($hotel->status) }}</span>
                </label>
                @endforeach
            </div>
        </div>

        @if($errors->any())
        <div style="background:#fee2e2;border-radius:9px;padding:10px 14px;margin-bottom:14px;color:#b91c1c;font-size:13px;">
            <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <button type="submit" style="width:100%;padding:11px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-paper-plane"></i> Send Campaign Now
        </button>
    </form>
</div>

{{-- ─── Campaign History ────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
    <div style="font-size:15px;font-weight:800;color:#1e293b;margin-bottom:16px;"><i class="fas fa-history" style="color:#06b6d4;margin-right:8px;"></i>Campaign History</div>

    @forelse($sentCampaigns as $c)
    <div style="padding:14px 16px;background:#f8fafc;border-radius:12px;margin-bottom:10px;border:1px solid #f1f5f9;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
            <div>
                <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $c->subject ?? '(No subject)' }}</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                    <span style="font-weight:600;">{{ strtoupper($c->channel) }}</span>
                    · {{ $c->template_key }}
                    · by {{ $c->sent_by }}
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px;font-weight:700;color:#7c3aed;">{{ $c->sent_count }} sent</div>
                <div style="font-size:10px;color:#94a3b8;">{{ \Carbon\Carbon::parse($c->sent_at)->diffForHumans() }}</div>
            </div>
        </div>
        <div style="font-size:12px;color:#64748b;line-height:1.5;overflow:hidden;max-height:40px;text-overflow:ellipsis;">
            {{ Str::limit($c->body, 120) }}
        </div>
    </div>
    @empty
    <div style="padding:40px;text-align:center;color:#94a3b8;font-style:italic;">No campaigns sent yet.</div>
    @endforelse
</div>

</div>

<script>
function toggleAllHotels(state) {
    document.querySelectorAll('.hotel-checkbox').forEach(el => el.checked = state);
}
</script>

@endsection
