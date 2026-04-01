@extends('layouts.platform')

@section('title', 'Deleted Guests')
@section('page-title', 'Deleted Guests')
@section('page-subtitle', 'View and restore soft-deleted guest profiles across all hotels')

@section('content')
<div class="page-content">

    {{-- Filter bar --}}
    <div style="background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);padding:18px 20px;margin-bottom:22px;">
        <form method="GET" action="{{ route('platform.guests.deleted') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label class="form-label">Filter by Hotel</label>
                <select name="hotel_id" class="form-input" onchange="this.form.submit()">
                    <option value="">All Hotels</option>
                    @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}" {{ $hotelId == $hotel->id ? 'selected' : '' }}>{{ $hotel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn-primary" style="padding:9px 18px;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                @if($hotelId)
                    <a href="{{ route('platform.guests.deleted') }}" class="btn-secondary" style="padding:9px 18px;margin-left:6px;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Stats --}}
    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:22px;">
        <div style="background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 1px 4px rgba(0,0,0,.06);flex:1;min-width:160px;">
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Total Deleted</div>
            <div style="font-size:26px;font-weight:800;color:#1e293b;margin-top:4px;">{{ $guests->total() }}</div>
        </div>
        @if($hotelId)
        <div style="background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 1px 4px rgba(0,0,0,.06);flex:1;min-width:160px;">
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Hotel Filter Active</div>
            <div style="font-size:14px;font-weight:700;color:#8b5cf6;margin-top:6px;">
                {{ $hotels->firstWhere('id', $hotelId)->name ?? 'Unknown' }}
            </div>
        </div>
        @endif
    </div>

    {{-- Table --}}
    <div style="background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-user-slash" style="color:#8b5cf6;"></i>
            <span style="font-size:14px;font-weight:700;color:#1e293b;">Soft-Deleted Guest Profiles</span>
        </div>

        @if($guests->isEmpty())
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fas fa-user-check" style="font-size:36px;margin-bottom:14px;display:block;color:#d1fae5;"></i>
            <p style="font-size:15px;font-weight:600;color:#475569;">No deleted guests found</p>
            <p style="font-size:13px;margin-top:4px;">{{ $hotelId ? 'This hotel has no deleted guest records.' : 'No guest records have been soft-deleted across any hotel.' }}</p>
        </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">
                        <th style="padding:12px 14px;text-align:left;">Guest</th>
                        <th style="padding:12px 14px;text-align:left;">Phone / Email</th>
                        <th style="padding:12px 14px;text-align:left;">Hotel</th>
                        <th style="padding:12px 14px;text-align:left;">ID Type</th>
                        <th style="padding:12px 14px;text-align:left;">Deleted At</th>
                        <th style="padding:12px 14px;text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guests as $guest)
                    <tr style="border-top:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 14px;">
                            <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $guest->name }}</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">ID #{{ $guest->id }}</div>
                        </td>
                        <td style="padding:12px 14px;">
                            <div style="font-size:13px;color:#334155;">{{ $guest->phone }}</div>
                            @if($guest->email)
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $guest->email }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 14px;">
                            <span class="badge-purple">{{ $guest->hotel_name }}</span>
                        </td>
                        <td style="padding:12px 14px;">
                            <span class="badge-gray" style="text-transform:capitalize;">{{ str_replace('_', ' ', $guest->id_type) }}</span>
                        </td>
                        <td style="padding:12px 14px;">
                            <div style="font-size:13px;color:#334155;">
                                {{ \Carbon\Carbon::parse($guest->deleted_at)->format('d M Y') }}
                            </div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                                {{ \Carbon\Carbon::parse($guest->deleted_at)->format('h:i A') }}
                                ({{ \Carbon\Carbon::parse($guest->deleted_at)->diffForHumans() }})
                            </div>
                        </td>
                        <td style="padding:12px 14px;text-align:center;">
                            <form method="POST" action="{{ route('platform.guests.restore', $guest->id) }}"
                                  onsubmit="return confirm('Restore guest {{ addslashes($guest->name) }} at {{ addslashes($guest->hotel_name) }}? They will become visible again in that hotel\'s guest list.');">
                                @csrf
                                <button type="submit" class="btn-success" style="font-size:11px;padding:6px 14px;">
                                    <i class="fas fa-rotate-left"></i> Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($guests->hasPages())
        <div style="padding:14px 20px;border-top:1px solid #f1f5f9;">
            {{ $guests->links() }}
        </div>
        @endif
        @endif
    </div>

    {{-- Info note --}}
    <div style="margin-top:18px;padding:14px 18px;background:#ede9fe;border-radius:12px;border:1px solid #ddd6fe;display:flex;gap:10px;align-items:flex-start;">
        <i class="fas fa-info-circle" style="color:#7c3aed;margin-top:2px;flex-shrink:0;"></i>
        <div style="font-size:13px;color:#5b21b6;">
            <strong>Note:</strong> Deleted guests are hidden from hotel staff but their data (bookings, invoices, payments) is preserved.
            Restoring a guest makes them fully visible in their hotel's guest list again.
            Permanent deletion is only possible via database administration.
        </div>
    </div>

</div>
@endsection
