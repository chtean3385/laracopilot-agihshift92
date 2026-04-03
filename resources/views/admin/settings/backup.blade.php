@extends('layouts.admin')
@section('title','Backup & Recovery')
@section('page-title','Backup & Recovery')
@section('page-subtitle','Manage your hotel data backups')
@section('content')

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:14px;">
    <i class="fas fa-check-circle" style="margin-right:8px;"></i>{{ session('success') }}
</div>
@endif
@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;">
    <i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>{{ $errors->first() }}
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:1100px;">

    {{-- Auto-Backup Settings --}}
    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-clock" style="color:#fff;font-size:15px;"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#1e293b;font-size:15px;">Auto-Backup Schedule</div>
                    <div style="font-size:12px;color:#64748b;">Configure automatic backup intervals</div>
                </div>
            </div>
        </div>
        <form action="{{ route('settings.backup.save') }}" method="POST" style="padding:24px;">
            @csrf
            <div style="margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <div style="position:relative;">
                        <input type="hidden" name="auto_backup_enabled" value="0">
                        <input type="checkbox" name="auto_backup_enabled" value="1" id="autoToggle"
                            {{ $setting->auto_backup_enabled ? 'checked' : '' }}
                            style="width:44px;height:24px;cursor:pointer;accent-color:#10b981;">
                    </div>
                    <div>
                        <div style="font-weight:700;color:#1e293b;font-size:14px;">Enable Auto-Backup</div>
                        <div style="font-size:12px;color:#64748b;">Automatically back up hotel data on a schedule</div>
                    </div>
                </label>
            </div>
            <div style="margin-bottom:20px;">
                <label class="form-label">Backup Interval</label>
                <select name="interval_hours" class="form-input">
                    @foreach([6=>'Every 6 hours',12=>'Every 12 hours',24=>'Every 24 hours (daily)',48=>'Every 48 hours',72=>'Every 3 days',168=>'Every week'] as $h => $label)
                    <option value="{{ $h }}" {{ $setting->interval_hours == $h ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:24px;">
                <label class="form-label">Keep Last (backups)</label>
                <select name="retention_count" class="form-input">
                    @foreach([3,5,7,10,15,20,30] as $n)
                    <option value="{{ $n }}" {{ $setting->retention_count == $n ? 'selected' : '' }}>{{ $n }} backups</option>
                    @endforeach
                </select>
                <p style="font-size:11px;color:#94a3b8;margin-top:4px;">Oldest backups beyond this limit are deleted automatically.</p>
            </div>
            @if($setting->last_backup_at)
            <div style="background:#f8fafc;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#64748b;">
                <i class="fas fa-history" style="margin-right:6px;color:#10b981;"></i>
                Last backup: <strong style="color:#1e293b;">{{ $setting->last_backup_at->format('d M Y, h:i A') }}</strong>
                @if($setting->auto_backup_enabled)
                — Next: <strong style="color:#1e293b;">{{ $setting->last_backup_at->addHours($setting->interval_hours)->format('d M Y, h:i A') }}</strong>
                @endif
            </div>
            @endif
            <button type="submit" class="btn-primary" style="width:100%;">
                <i class="fas fa-save" style="margin-right:6px;"></i>Save Settings
            </button>
        </form>
    </div>

    {{-- Manual Backup --}}
    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#eff6ff,#dbeafe);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-database" style="color:#fff;font-size:15px;"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#1e293b;font-size:15px;">Manual Backup</div>
                    <div style="font-size:12px;color:#64748b;">Create an instant snapshot now</div>
                </div>
            </div>
        </div>
        <div style="padding:24px;">
            <div style="background:#f8fafc;border-radius:14px;padding:18px;margin-bottom:20px;font-size:13px;color:#475569;line-height:1.6;">
                <p style="font-weight:700;color:#1e293b;margin:0 0 8px;"><i class="fas fa-info-circle text-blue-500" style="margin-right:6px;"></i>What gets backed up</p>
                <ul style="margin:0;padding-left:18px;">
                    <li>Hotel settings & branding</li>
                    <li>All rooms & pricing</li>
                    <li>All guests / customers</li>
                    <li>All bookings with check-in/out history</li>
                    <li>All payments & invoices</li>
                </ul>
            </div>
            <form action="{{ route('settings.backup.store') }}" method="POST"
                  onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\' style=\'margin-right:6px;\'></i>Creating backup…';">
                @csrf
                <button type="submit" style="width:100%;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:12px;padding:12px 20px;font-size:14px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-cloud-upload-alt" style="margin-right:8px;"></i>Create Backup Now
                </button>
            </form>
            <p style="font-size:11px;color:#94a3b8;margin-top:10px;text-align:center;">
                {{ $backups->count() }} backup{{ $backups->count() !== 1 ? 's' : '' }} stored
                (retention: keep {{ $setting->retention_count }})
            </p>
        </div>
    </div>

</div>

{{-- Backup History --}}
<div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;margin-top:24px;max-width:1100px;">
    <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#fafafa,#f8fafc);">
        <div style="font-weight:800;color:#1e293b;font-size:15px;"><i class="fas fa-history" style="color:#64748b;margin-right:8px;"></i>Backup History</div>
    </div>
    @if($backups->isEmpty())
    <div style="padding:48px 24px;text-align:center;color:#94a3b8;">
        <i class="fas fa-archive" style="font-size:40px;display:block;margin-bottom:12px;opacity:.3;"></i>
        <p style="font-size:15px;font-weight:600;margin:0 0 4px;color:#64748b;">No backups yet</p>
        <p style="font-size:13px;margin:0;">Create your first backup using the button above.</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 20px;">Backup</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Type</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Size</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Created</th>
                    <th style="text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 20px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $backup)
                <tr style="border-bottom:1px solid #f8fafc;">
                    <td style="padding:14px 20px;font-size:13px;font-weight:600;color:#1e293b;">
                        {{ $backup->label ?? 'Backup #' . $backup->id }}
                    </td>
                    <td style="padding:14px 14px;">
                        @if($backup->type === 'auto')
                        <span style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">Auto</span>
                        @else
                        <span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">Manual</span>
                        @endif
                    </td>
                    <td style="padding:14px 14px;font-size:13px;color:#64748b;">{{ number_format($backup->size_kb) }} KB</td>
                    <td style="padding:14px 14px;font-size:13px;color:#64748b;">{{ $backup->created_at->format('d M Y, h:i A') }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        <form action="{{ route('settings.backup.destroy', $backup->id) }}" method="POST"
                              onsubmit="return confirm('Delete this backup? This cannot be undone.');">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:#fee2e2;color:#b91c1c;border:none;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
