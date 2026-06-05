@extends('layouts.admin')
@section('title','Deleted Invoices')
@section('page-title','Deleted Invoices')
@section('page-subtitle','Invoices can be restored within 30 days of deletion')

@section('content')
<div class="space-y-4">
    {{-- Header bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <a href="{{ route('invoices.index') }}" class="btn-secondary text-sm w-fit">
            <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
        </a>
        <form method="GET" action="{{ route('invoices.trash') }}" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search by invoice # or guest…"
                class="form-input text-sm w-56">
            <button type="submit" class="btn-primary text-sm">Search</button>
            @if(request('search'))
            <a href="{{ route('invoices.trash') }}" class="btn-secondary text-sm">Clear</a>
            @endif
        </form>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-check-circle"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
    </div>
    @endif

    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-700 flex items-start gap-2">
        <i class="fas fa-info-circle mt-0.5 flex-shrink-0"></i>
        <span>Deleted invoices are permanently removed after <strong>30 days</strong>. Restoring an invoice also reinstates the booking's payment status.</span>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($invoices->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400">
            <i class="fas fa-trash-alt text-4xl mb-3 block opacity-40"></i>
            <p class="font-semibold text-gray-500">No deleted invoices found</p>
            <p class="text-sm mt-1">Deleted invoices will appear here until they are permanently purged.</p>
        </div>
        @else
        <div class="overflow-x-auto">
        <table class="w-full min-w-[640px]">
            <thead class="bg-slate-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Invoice #</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Booking</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Deleted</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Expires</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($invoices as $invoice)
                @php
                    $daysLeft  = (int) now()->diffInDays($invoice->deleted_at->addDays(30), false);
                    $expired   = $daysLeft <= 0;
                    $urgentCss = !$expired && $daysLeft <= 7 ? 'text-amber-600 font-semibold' : 'text-gray-500';
                @endphp
                <tr class="hover:bg-gray-50 transition-colors {{ $expired ? 'opacity-50' : '' }}">
                    <td class="px-5 py-3 font-mono text-sm font-bold text-gray-700">{{ $invoice->invoice_number }}</td>
                    <td class="px-5 py-3 text-sm text-gray-700">{{ $invoice->customer?->name ?? '(Deleted Guest)' }}</td>
                    <td class="px-5 py-3 text-sm text-gray-500">
                        {{ $invoice->booking?->booking_number ?? '—' }}
                        @if($invoice->booking?->room)
                        <span class="text-xs text-gray-400">· Rm {{ $invoice->booking->room->room_number }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-sm font-bold text-right text-gray-800">₹{{ number_format($invoice->total_amount) }}</td>
                    <td class="px-5 py-3 text-sm text-gray-500">{{ $invoice->deleted_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-sm {{ $urgentCss }}">
                        @if($expired)
                            <span class="text-red-500 font-semibold">Expired</span>
                        @elseif($daysLeft === 0)
                            <span class="text-red-500 font-semibold">Today</span>
                        @else
                            {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} left
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if(!$expired)
                        @canDo('invoices.delete')
                        <form method="POST" action="{{ route('invoices.restore', $invoice->id) }}"
                              onsubmit="return confirm('Restore invoice {{ $invoice->invoice_number }}?')">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors">
                                <i class="fas fa-undo-alt"></i>Restore
                            </button>
                        </form>
                        @endCanDo
                        @else
                        <span class="text-xs text-gray-400 italic">Cannot restore</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        @if($invoices->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $invoices->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
