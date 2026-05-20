@extends('layouts.admin')
@section('title', 'OTA Email Parser')
@section('page-title', 'OTA Email Parser')
@section('page-subtitle', 'Auto-import OTA booking confirmations from your email inbox via IMAP every 5 minutes.')

@section('content')
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 18px;border-radius:12px;margin-bottom:18px;font-weight:600;">
    <i class="fas fa-times-circle"></i> {{ session('error') }}
</div>
@endif

@if($unresolvedConflicts > 0)
<div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:12px 18px;border-radius:12px;margin-bottom:18px;font-weight:600;display:flex;align-items:center;justify-content:space-between;">
    <span><i class="fas fa-triangle-exclamation"></i> {{ $unresolvedConflicts }} OTA booking conflict{{ $unresolvedConflicts === 1 ? '' : 's' }} need your attention.</span>
    <a href="{{ route('email-parser.conflicts') }}" style="color:#9a3412;text-decoration:underline;">Review now →</a>
</div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start;">

    {{-- ── Config form ──────────────────────────────────────────────── --}}
    <div style="background:#fff;border-radius:20px;padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:40px;height:40px;background:linear-gradient(135deg,#0d9488,#0f766e);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-envelope-open-text" style="color:#fff;"></i>
            </div>
            <div>
                <div style="font-size:16px;font-weight:800;color:#1e293b;">IMAP Inbox Configuration</div>
                <div style="font-size:12px;color:#64748b;">Sync runs automatically every 5 minutes for active configs.</div>
            </div>
        </div>

        <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
            <button type="button" onclick="quickFill('gmail')" class="qf-btn"><i class="fab fa-google"></i> Gmail</button>
            <button type="button" onclick="quickFill('outlook')" class="qf-btn"><i class="fab fa-microsoft"></i> Outlook / Hotmail</button>
            <button type="button" onclick="quickFill('yahoo')" class="qf-btn"><i class="fab fa-yahoo"></i> Yahoo</button>
        </div>

        <form id="ep-form" action="{{ route('email-parser.config.save') }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="ep-lbl">Email Address</label>
                    <input type="email" name="email_address" value="{{ old('email_address', $config->email_address ?? '') }}" required class="ep-inp">
                </div>
                <div>
                    <label class="ep-lbl">Password / App Password</label>
                    <input type="password" name="email_password" placeholder="{{ $config ? '••••••• (leave blank to keep)' : '' }}" class="ep-inp">
                </div>
                <div>
                    <label class="ep-lbl">IMAP Host</label>
                    <input type="text" name="imap_host" value="{{ old('imap_host', $config->imap_host ?? 'imap.gmail.com') }}" required class="ep-inp">
                </div>
                <div>
                    <label class="ep-lbl">Port</label>
                    <input type="number" name="imap_port" value="{{ old('imap_port', $config->imap_port ?? 993) }}" required class="ep-inp">
                </div>
                <div>
                    <label class="ep-lbl">Encryption</label>
                    <select name="encryption" class="ep-inp">
                        @foreach(['ssl','tls','none'] as $opt)
                        <option value="{{ $opt }}" @selected(old('encryption', $config->encryption ?? 'ssl') === $opt)>{{ strtoupper($opt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ep-lbl">Folder to Watch</label>
                    <input type="text" name="folder_to_watch" value="{{ old('folder_to_watch', $config->folder_to_watch ?? 'INBOX') }}" class="ep-inp">
                </div>
            </div>

            <div style="margin-top:16px;display:flex;align-items:center;gap:10px;">
                <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $config->is_active ?? true))>
                <label for="is_active" style="font-size:13px;color:#334155;font-weight:600;">Active — sync this inbox every 5 minutes</label>
            </div>

            {{-- ── Allowed Senders Whitelist ──────────────────────────────── --}}
            <div style="margin-top:20px;">
                <label style="font-size:13px;font-weight:700;color:#334155;display:block;margin-bottom:6px;">
                    <i class="fas fa-filter" style="color:#0d9488;"></i> Allowed Senders (Whitelist)
                </label>
                <div style="font-size:12px;color:#64748b;margin-bottom:10px;">
                    Only fetch emails from these addresses. Leave empty to accept all senders.
                </div>

                {{-- Tag chips display --}}
                <div id="senderTags" style="display:flex;flex-wrap:wrap;gap:7px;min-height:38px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 10px;align-items:center;margin-bottom:8px;">
                    @php $existingSenders = $config->allowed_senders ?? []; @endphp
                    @foreach($existingSenders as $s)
                    <span class="sender-tag" data-email="{{ $s }}" style="display:inline-flex;align-items:center;gap:5px;background:#ccfbf1;color:#0f766e;border-radius:20px;padding:3px 11px 3px 11px;font-size:12px;font-weight:700;">
                        {{ $s }}
                        <button type="button" onclick="removeSender(this)" style="background:none;border:none;color:#0f766e;cursor:pointer;padding:0;line-height:1;font-size:13px;">&times;</button>
                    </span>
                    @endforeach
                    <span id="senderPlaceholder" style="font-size:12px;color:#94a3b8;{{ count($existingSenders) > 0 ? 'display:none;' : '' }}">No senders added — all emails accepted</span>
                </div>

                {{-- Add sender input row --}}
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="email" id="senderInput" placeholder="e.g. noreply@booking.com"
                        style="flex:1;border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 12px;font-size:13px;color:#334155;outline:none;"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();addSender();}">
                    <button type="button" onclick="addSender()"
                        style="background:#0d9488;color:#fff;border:none;padding:9px 16px;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;white-space:nowrap;">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>

                {{-- Hidden field submitted with form --}}
                <input type="hidden" name="allowed_senders" id="allowedSendersHidden"
                    value="{{ implode(',', $existingSenders) }}">
            </div>

            <div style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-save"></i> Save Configuration
                </button>
                <button type="button" onclick="testConnection()" style="background:#f8fafc;color:#0f766e;border:1.5px solid #0d9488;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-plug"></i> Test Connection
                </button>
                <button type="button" onclick="document.getElementById('simEmailModal').style.display='flex'" style="background:#f8fafc;color:#6366f1;border:1.5px solid #6366f1;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-flask"></i> Simulate Email
                </button>
                <button type="button" id="syncNowBtn" onclick="syncNow()" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-sync-alt"></i> Sync Now
                </button>
            </div>

            <div id="ep-test-result" style="margin-top:14px;font-size:13px;font-weight:600;"></div>
            <div id="ep-sync-result" style="margin-top:8px;font-size:13px;font-weight:600;"></div>
        </form>

        @if($config)
        <form action="{{ route('email-parser.toggle-active') }}" method="POST" style="margin-top:12px;">
            @csrf
            <button type="submit" style="background:{{ $config->is_active ? '#fee2e2' : '#dcfce7' }};color:{{ $config->is_active ? '#b91c1c' : '#15803d' }};border:none;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                <i class="fas fa-toggle-{{ $config->is_active ? 'off' : 'on' }}"></i>
                {{ $config->is_active ? 'Pause Sync' : 'Resume Sync' }}
            </button>
        </form>
        @endif

        <div style="margin-top:16px;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:14px;padding:16px;">
            <div style="font-weight:800;color:#92400e;margin-bottom:8px;font-size:14px;">
                <i class="fas fa-triangle-exclamation" style="color:#d97706;"></i>
                Gmail INBOX performance tip — important!
            </div>
            <p style="margin:0 0 10px;font-size:13px;color:#78350f;line-height:1.6;">
                If your Gmail INBOX has many emails (500+), the IMAP sync hangs because Gmail sends the full mailbox state on connect.
                <strong>Create a dedicated Gmail Label</strong> instead — it connects in under 2 seconds.
            </p>
            <ol style="margin:0;padding-left:22px;font-size:13px;color:#78350f;line-height:1.9;">
                <li>In Gmail → left sidebar → <strong>+ Create new label</strong> → name it e.g. <code>OTA Bookings</code></li>
                <li>Gmail Settings → <strong>Filters and Blocked Addresses → Create a new filter</strong></li>
                <li>In "From" field: add OTA sender addresses (e.g. <code>noreply@booking.com, noreply@goibibo.com</code>)</li>
                <li>Click "Create filter" → tick <strong>Apply the label → OTA Bookings</strong> → Save</li>
                <li>Back in the CRM — set <strong>Folder to Watch</strong> to <code>OTA Bookings</code> and save</li>
            </ol>
        </div>

        <div style="margin-top:14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;">
            <div style="font-weight:800;color:#1e293b;margin-bottom:8px;font-size:14px;"><i class="fab fa-google" style="color:#ea4335;"></i> Using Gmail? Generate an App Password:</div>
            <ol style="margin:0;padding-left:22px;font-size:13px;color:#475569;line-height:1.8;">
                <li>Go to <strong>Google Account → Security → 2-Step Verification</strong> (must be enabled).</li>
                <li>Scroll down to <strong>App Passwords</strong> and create one for "Mail".</li>
                <li>Use <code>imap.gmail.com</code> · Port <code>993</code> · Encryption <code>SSL</code>.</li>
                <li>Paste the 16-character App Password (no spaces) above.</li>
            </ol>
        </div>
    </div>

    {{-- ── Sync status + recent emails ──────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
        <div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:10px;">Sync Status</div>
            <div style="font-size:13px;color:#64748b;margin-bottom:6px;">Last synced: <strong style="color:#1e293b;">{{ $config?->last_synced_at?->diffForHumans() ?? 'Never' }}</strong></div>
            <div style="font-size:13px;color:#64748b;margin-bottom:6px;">Active: <strong style="color:{{ $config?->is_active ? '#15803d' : '#94a3b8' }};">{{ $config && $config->is_active ? 'Yes' : 'No' }}</strong></div>
            <div style="font-size:12px;color:#94a3b8;margin-top:10px;"><i class="fas fa-check-circle" style="color:#10b981;"></i> Scheduler is running automatically — email sync happens every 5 minutes while the app is active.</div>
            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('email-parser.logs') }}" style="background:#f1f5f9;color:#334155;padding:8px 14px;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;"><i class="fas fa-list"></i> View Logs</a>
                <a href="{{ route('email-parser.conflicts') }}" style="background:#fff7ed;color:#9a3412;padding:8px 14px;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;"><i class="fas fa-triangle-exclamation"></i> Conflicts ({{ $unresolvedConflicts }})</a>
            </div>
        </div>

        <div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
            <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:12px;">Last 20 Emails</div>
            @if($latestEmails->isEmpty())
            <div style="color:#94a3b8;font-size:13px;">No emails parsed yet.</div>
            @else
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($latestEmails as $em)
                <div style="border:1px solid #f1f5f9;border-radius:10px;padding:10px;">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                        <div style="font-size:12px;font-weight:700;color:#1e293b;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $em->subject ?: '(no subject)' }}</div>
                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:{{ $em->status_color }}22;color:{{ $em->status_color }};">{{ $em->status }}</span>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:3px;">{{ $em->sender }} · {{ $em->created_at?->diffForHumans() }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

<style>
.ep-lbl { display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; }
.ep-inp { width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; outline:none; transition:border-color .15s; }
.ep-inp:focus { border-color:#0d9488; }
.qf-btn { background:#f8fafc; border:1.5px solid #e2e8f0; padding:8px 14px; border-radius:10px; font-size:12px; font-weight:700; color:#475569; cursor:pointer; }
.qf-btn:hover { background:#0d948811; border-color:#0d9488; color:#0f766e; }
@media (max-width: 900px) {
    [style*="grid-template-columns:2fr 1fr"] { grid-template-columns: 1fr !important; }
    [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

{{-- ── Simulate Email Modal ──────────────────────────────────────────────── --}}
<div id="simEmailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:90%;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,.2);position:relative;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-flask" style="color:#fff;font-size:15px;"></i>
                </div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">Simulate Email Parsing</div>
                    <div style="font-size:11px;color:#94a3b8;">Paste an OTA email to test the parser — nothing is saved</div>
                </div>
            </div>
            <button onclick="closeSimModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:20px;line-height:1;">&times;</button>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Sender Email <span style="font-weight:400;color:#94a3b8;">(optional — used for OTA matching)</span></label>
                <input id="sim-sender" type="text" placeholder="e.g. noreply@booking.com" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Subject <span style="font-weight:400;color:#94a3b8;">(optional — used for OTA matching)</span></label>
                <input id="sim-subject" type="text" placeholder="e.g. New Booking Confirmation – BDC-12345" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Email Body <span style="color:#b91c1c;">*</span></label>
                <textarea id="sim-body" rows="9" placeholder="Paste the plain-text body of the OTA booking confirmation here..." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:12px;font-family:monospace;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
        </div>

        <div style="margin-top:14px;display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:10px 14px;">
            <input type="checkbox" id="sim-create-booking" style="width:16px;height:16px;cursor:pointer;">
            <label for="sim-create-booking" style="font-size:13px;font-weight:700;color:#15803d;cursor:pointer;">Also create booking in CRM (like WhatsApp simulate)</label>
        </div>

        <div style="margin-top:12px;display:flex;gap:10px;">
            <button onclick="runSimEmail()" style="flex:1;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-play"></i> Run Parser
            </button>
            <button onclick="closeSimModal()" style="background:#f1f5f9;border:none;padding:11px 18px;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
        </div>

        <div id="sim-result" style="margin-top:16px;"></div>
    </div>
</div>

<script>
const QUICK_FILL = {
    gmail:   { host: 'imap.gmail.com',          port: 993, enc: 'ssl' },
    outlook: { host: 'outlook.office365.com',   port: 993, enc: 'ssl' },
    yahoo:   { host: 'imap.mail.yahoo.com',     port: 993, enc: 'ssl' },
};
function quickFill(key) {
    const f = QUICK_FILL[key];
    if (!f) return;
    document.querySelector('[name=imap_host]').value = f.host;
    document.querySelector('[name=imap_port]').value = f.port;
    document.querySelector('[name=encryption]').value = f.enc;
}
function closeSimModal() {
    document.getElementById('simEmailModal').style.display = 'none';
    document.getElementById('sim-result').innerHTML = '';
}
async function runSimEmail() {
    const out = document.getElementById('sim-result');
    const body = document.getElementById('sim-body').value.trim();
    const createBooking = document.getElementById('sim-create-booking').checked;
    if (!body) { out.innerHTML = '<p style="color:#b91c1c;font-size:13px;"><i class="fas fa-exclamation-circle"></i> Please paste an email body.</p>'; return; }
    out.innerHTML = '<p style="color:#64748b;font-size:13px;"><i class="fas fa-spinner fa-spin"></i> ' + (createBooking ? 'Parsing & creating booking...' : 'Parsing...') + '</p>';
    try {
        const fd = new FormData();
        fd.append('sender',         document.getElementById('sim-sender').value.trim());
        fd.append('subject',        document.getElementById('sim-subject').value.trim());
        fd.append('body',           body);
        fd.append('create_booking', createBooking ? '1' : '0');
        const r = await fetch('{{ route('email-parser.simulate') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: fd,
        });
        const j = await r.json();

        // Build parsed fields table (shown in both success and parse_ok cases)
        const fieldRows = j.data ? Object.entries(j.data).map(([k, v]) =>
            '<tr><td style="padding:4px 10px;font-weight:700;color:#475569;font-size:12px;white-space:nowrap;">' + k.replace(/_/g,' ') + '</td>'
            + '<td style="padding:4px 10px;color:#1e293b;font-size:12px;">' + (v ?? '—') + '</td></tr>'
        ).join('') : '';

        if (j.ok && j.created) {
            // Booking was created
            out.innerHTML = '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px;">'
                + '<div style="font-size:13px;font-weight:800;color:#15803d;margin-bottom:8px;"><i class="fas fa-check-circle"></i> Booking Created! Matched: ' + j.ota_label + '</div>'
                + '<table style="width:100%;border-collapse:collapse;">' + fieldRows + '</table>'
                + '<div style="margin-top:10px;"><a href="{{ route('bookings.index') }}" style="color:#0f766e;font-size:12px;font-weight:700;text-decoration:underline;"><i class="fas fa-arrow-right"></i> View in Bookings</a></div>'
                + '</div>';
        } else if (!j.ok && j.parsed_ok) {
            // Parsed OK but booking creation failed (e.g. missing fields, room conflict)
            out.innerHTML = '<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;">'
                + '<div style="font-size:13px;font-weight:800;color:#9a3412;margin-bottom:8px;"><i class="fas fa-exclamation-triangle"></i> Parsed OK · Booking not created</div>'
                + '<div style="font-size:12px;color:#9a3412;margin-bottom:8px;">' + (j.message || '') + '</div>'
                + (fieldRows ? '<table style="width:100%;border-collapse:collapse;">' + fieldRows + '</table>' : '')
                + '</div>';
        } else if (j.ok) {
            // Preview only (create_booking unchecked)
            out.innerHTML = '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px;">'
                + '<div style="font-size:13px;font-weight:800;color:#15803d;margin-bottom:8px;"><i class="fas fa-check-circle"></i> Matched: ' + j.ota_label + '</div>'
                + '<table style="width:100%;border-collapse:collapse;">' + fieldRows + '</table>'
                + '</div>';
        } else {
            out.innerHTML = '<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:12px;color:#b91c1c;font-size:13px;"><i class="fas fa-times-circle"></i> ' + (j.message || 'Parser returned no match.') + '</div>';
        }
    } catch (e) {
        out.innerHTML = '<div style="color:#b91c1c;font-size:13px;"><i class="fas fa-times-circle"></i> Network error: ' + e.message + '</div>';
    }
}
async function syncNow() {
    const btn = document.getElementById('syncNowBtn');
    const out = document.getElementById('ep-sync-result');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    out.innerHTML = '<span style="color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Connecting to Gmail IMAP — please wait up to 60 seconds...</span>';
    try {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 70000);
        const r = await fetch('{{ route('email-parser.sync-now') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            signal: controller.signal,
        });
        clearTimeout(timer);
        const j = await r.json();
        if (j.ok) {
            const icon = j.created > 0 ? 'fa-calendar-plus' : (j.fetched > 0 ? 'fa-envelope-open' : 'fa-check-circle');
            const color = j.created > 0 ? '#15803d' : '#0f766e';
            out.innerHTML = '<span style="color:' + color + ';"><i class="fas ' + icon + '"></i> ' + j.message + '</span>';
            // Refresh page after 2s so the email list updates
            if (j.fetched > 0 || j.parsed > 0) setTimeout(() => location.reload(), 2000);
        } else {
            out.innerHTML = '<span style="color:#b91c1c;"><i class="fas fa-times-circle"></i> ' + j.message + '</span>';
        }
    } catch (e) {
        out.innerHTML = '<span style="color:#b91c1c;"><i class="fas fa-times-circle"></i> Network error: ' + e.message + '</span>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync Now';
    }
}
function syncSendersHidden() {
    const tags = document.querySelectorAll('#senderTags .sender-tag');
    const emails = Array.from(tags).map(t => t.dataset.email);
    document.getElementById('allowedSendersHidden').value = emails.join(',');
    document.getElementById('senderPlaceholder').style.display = emails.length ? 'none' : '';
}
function addSender() {
    const input = document.getElementById('senderInput');
    const val = input.value.trim().toLowerCase();
    if (!val) return;
    // Basic email check
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        input.style.borderColor = '#fca5a5';
        setTimeout(() => input.style.borderColor = '#e2e8f0', 1500);
        return;
    }
    // Duplicate check
    const existing = Array.from(document.querySelectorAll('#senderTags .sender-tag')).map(t => t.dataset.email);
    if (existing.includes(val)) { input.value = ''; return; }
    // Build chip
    const chip = document.createElement('span');
    chip.className = 'sender-tag';
    chip.dataset.email = val;
    chip.style.cssText = 'display:inline-flex;align-items:center;gap:5px;background:#ccfbf1;color:#0f766e;border-radius:20px;padding:3px 11px;font-size:12px;font-weight:700;';
    chip.innerHTML = val + '<button type="button" onclick="removeSender(this)" style="background:none;border:none;color:#0f766e;cursor:pointer;padding:0;line-height:1;font-size:13px;">&times;</button>';
    document.getElementById('senderTags').appendChild(chip);
    input.value = '';
    syncSendersHidden();
}
function removeSender(btn) {
    btn.closest('.sender-tag').remove();
    syncSendersHidden();
}
async function testConnection() {
    const out = document.getElementById('ep-test-result');
    out.innerHTML = '<span style="color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Testing...</span>';
    const form = document.getElementById('ep-form');
    const fd = new FormData(form);
    try {
        const r = await fetch('{{ route('email-parser.test-connection') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: fd,
        });
        const j = await r.json();
        if (j.ok) {
            out.innerHTML = '<span style="color:#15803d;"><i class="fas fa-check-circle"></i> ' + j.message + '</span>';
        } else {
            out.innerHTML = '<span style="color:#b91c1c;"><i class="fas fa-times-circle"></i> ' + (j.message || 'Connection failed') + '</span>';
        }
    } catch (e) {
        out.innerHTML = '<span style="color:#b91c1c;"><i class="fas fa-times-circle"></i> Network error: ' + e.message + '</span>';
    }
}
</script>
@endsection
