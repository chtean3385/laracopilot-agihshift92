@extends('layouts.admin')
@section('title','Invoices')
@section('page-title','Invoices')
@section('page-subtitle','All generated invoices')
@section('content')
<div class="space-y-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="relative flex-1 min-w-[220px]">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Invoice #, guest name, room, amount..." class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
            </div>
            <select name="status" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">All Statuses</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
            <button type="submit" class="btn-primary text-sm"><i class="fas fa-search mr-1"></i>Search</button>
            @if(request()->anyFilled(['search','status','date_from','date_to']))
            <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
            @endif
        </form>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Invoice #</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Paid</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Balance</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-xs font-mono text-violet-600 font-bold">{{ $invoice->invoice_number }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $invoice->customer->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $invoice->booking->room->room_number ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-700">₹{{ number_format($invoice->total_amount) }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-emerald-600">₹{{ number_format($invoice->paid_amount) }}</td>
                        <td class="px-6 py-4 text-sm font-bold {{ $invoice->balance > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($invoice->balance) }}</td>
                        <td class="px-6 py-4">
                            <span class="badge-{{ $invoice->status == 'paid' ? 'green' : ($invoice->status == 'partial' ? 'yellow' : 'red') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-400">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : 'N/A' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('invoices.show', $invoice->id) }}" class="w-8 h-8 flex items-center justify-center bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-all" title="View">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('invoices.print', $invoice->id) }}" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg transition-all" title="Print" target="_blank">
                                    <i class="fas fa-print text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-6 py-16 text-center text-gray-400"><i class="fas fa-file-invoice text-4xl mb-3"></i><p>No invoices yet</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $invoices->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
