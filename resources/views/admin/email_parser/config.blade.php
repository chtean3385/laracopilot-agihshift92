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

            <div style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-save"></i> Save Configuration
                </button>
                <button type="button" onclick="testConnection()" style="background:#f8fafc;color:#0f766e;border:1.5px solid #0d9488;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-plug"></i> Test Connection
                </button>
            </div>

            <div id="ep-test-result" style="margin-top:14px;font-size:13px;font-weight:600;"></div>
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

        <div style="margin-top:22px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;">
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
            <div style="font-size:12px;color:#94a3b8;margin-top:10px;">Sync runs via the scheduler. Make sure <code>php artisan schedule:run</code> is hooked into cron every minute.</div>
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
