@extends('layouts.admin')
@section('title','Room Mapping')
@section('page-title','Room Mapping')
@section('page-subtitle','Link CRM rooms to OTA room type codes')

@section('content')
<div style="display:grid;gap:20px;">

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;color:#15803d;font-weight:600;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle"></i>{{ session('success') }}
    </div>
    @endif

    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
            <h3 style="font-weight:800;font-size:15px;color:#1e293b;"><i class="fas fa-bed" style="color:#7c3aed;margin-right:8px;"></i>Room Mapping</h3>
            @php $mapped = $mappings->filter(fn($m) => !empty($m->channel_room_code))->count(); @endphp
            <span style="background:{{ $mapped === $rooms->count() ? '#f0fdf4' : '#fef9c3' }};color:{{ $mapped === $rooms->count() ? '#15803d' : '#854d0e' }};padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;">
                {{ $mapped }} of {{ $rooms->count() }} rooms mapped
            </span>
        </div>
        <p style="font-size:12px;color:#94a3b8;margin-bottom:20px;">Enter the room type code from your channel manager for each room. Leave blank to exclude a room from OTA sync.</p>

        <form action="{{ route('channel_manager.rooms.save') }}" method="POST">
            @csrf
            <div class="lv-table-wrap">
                <table class="lv-table">
                    <thead><tr>
                        <th>Room #</th>
                        <th>Type</th>
                        <th>CRM Rate</th>
                        <th>Channel Room Code <span style="color:#e11d48;">*</span></th>
                        <th>Rate Plan Code</th>
                        <th>Extra Bed Rate</th>
                        <th>Status</th>
                    </tr></thead>
                    <tbody>
                    @foreach($rooms as $room)
                    @php $m = $mappings->get($room->id); @endphp
                    <tr>
                        <td style="font-weight:800;color:#1e293b;">{{ $room->room_number }}</td>
                        <td style="color:#64748b;font-size:12px;">{{ ucfirst($room->type) }}</td>
                        <td style="font-weight:600;">Rs{{ number_format($room->price_per_night) }}</td>
                        <td>
                            <input type="text" name="rooms[{{ $room->id }}][channel_room_code]"
                                value="{{ $m?->channel_room_code }}"
                                placeholder="e.g. DBL, SGL, SUITE"
                                style="width:120px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:monospace;">
                        </td>
                        <td>
                            <input type="text" name="rooms[{{ $room->id }}][channel_rate_plan]"
                                value="{{ $m?->channel_rate_plan }}"
                                placeholder="e.g. BAR, PKG"
                                style="width:90px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;">
                        </td>
                        <td>
                            <input type="number" name="rooms[{{ $room->id }}][extra_bed_rate]"
                                value="{{ $m?->extra_bed_rate ?? 0 }}"
                                min="0" step="0.01"
                                style="width:90px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;">
                        </td>
                        <td>
                            @if($m && $m->channel_room_code)
                                <span style="background:#f0fdf4;color:#15803d;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;"><i class="fas fa-check"></i> Mapped</span>
                            @else
                                <span style="background:#fef9c3;color:#854d0e;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;"><i class="fas fa-exclamation"></i> Unmapped</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px;">
                <button type="submit" style="padding:11px 28px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;">
                    <i class="fas fa-save" style="margin-right:6px;"></i>Save All Mappings
                </button>
                <a href="{{ route('channel_manager.index') }}" style="padding:11px 20px;background:#f1f5f9;color:#475569;border-radius:10px;font-weight:600;font-size:13px;text-decoration:none;">Cancel</a>
            </div>
        </form>
    </div>

    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;">
        <p style="font-size:12px;color:#1e40af;font-weight:600;"><i class="fas fa-info-circle" style="margin-right:6px;"></i><strong>What is a Channel Room Code?</strong> It's the room type identifier your OTA channel manager uses. For eZee it's usually like <code>DBL</code>, <code>SGL</code>, <code>STE</code>. Check your channel manager dashboard → Room Types to find these codes.</p>
    </div>

</div>
@endsection
