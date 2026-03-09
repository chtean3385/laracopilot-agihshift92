<div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-5 text-white">
            <p class="text-sm text-emerald-100">Total Revenue</p>
            <p class="text-3xl font-black mt-1">₹{{ number_format($totalRevenue) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Showing</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $payments->total() }} Transactions</p>
        </div>
        <div class="flex items-center justify-end">
            <a href="{{ route('payments.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i>Record Payment</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="relative flex-1 min-w-[220px]">
                <label class="form-label">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Transaction ID, guest name, booking #, amount..."
                        class="w-full border border-gray-200 rounded-xl pl-9 pr-9 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none"
                    >
                    <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-cyan-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div>
                <label class="form-label">Payment Method</label>
                <select wire:model.live="paymentMethod" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                    <option value="">All Methods</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="upi">UPI</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div>
                <label class="form-label">Date From</label>
                <input type="date" wire:model.live="dateFrom" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" wire:model.live="dateTo" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
            </div>
            @if($search || $paymentMethod || $dateFrom || $dateTo)
            <div class="self-end pb-0.5">
                <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</button>
            </div>
            @endif
        </div>
        @if($search || $paymentMethod || $dateFrom || $dateTo)
        <p class="text-xs text-cyan-600 font-medium mt-3">
            <i class="fas fa-filter mr-1"></i>Showing {{ $payments->total() }} result(s) — filters active
        </p>
        @endif
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Transaction ID</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $payment->transaction_id }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $payment->booking->customer->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-xs font-mono text-cyan-600">{{ $payment->booking->booking_number ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm font-bold text-emerald-600">₹{{ number_format($payment->amount) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($payment->payment_method) }}</td>
                        <td class="px-6 py-4"><span class="badge-blue">{{ ucfirst($payment->payment_type) }}</span></td>
                        <td class="px-6 py-4 text-xs text-gray-400">{{ $payment->created_at->format('d M Y h:i A') }}</td>
                        <td class="px-6 py-4 text-right"><a href="{{ route('payments.show', $payment->id) }}" class="text-cyan-600 hover:underline text-xs">View</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center text-gray-400">
                            <i class="fas fa-credit-card text-4xl mb-3"></i>
                            <p>No payment records found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $payments->links() }}</div>
    </div>
</div>
