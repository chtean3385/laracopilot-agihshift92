<div>

    {{-- Stats row --}}
    <div class="lv-stats-grid">
        <div class="lv-stat-card-accent" style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 16px rgba(16,185,129,.25);">
            <div class="lv-stat-label" style="color:rgba(255,255,255,.75);">TOTAL REVENUE</div>
            <div class="lv-stat-value" style="color:#fff;">₹{{ number_format($totalRevenue) }}</div>
            <div class="lv-stat-sub" style="color:rgba(255,255,255,.6);">All completed payments</div>
        </div>
        <div class="lv-stat-card">
            <div class="lv-stat-label" style="color:#94a3b8;">TRANSACTIONS</div>
            <div class="lv-stat-value" style="color:#1e293b;">{{ $payments->total() }}</div>
            <div class="lv-stat-sub" style="color:#94a3b8;">
                <span wire:loading.remove>{{ ($search||$paymentMethod||$dateFrom||$dateTo) ? 'Filtered results' : 'All records' }}</span>
                <span wire:loading style="color:#10b981;"><i class="fas fa-circle-notch fa-spin"></i> Updating…</span>
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:flex-end;">
            <a href="{{ route('payments.create') }}" class="btn-primary" style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 12px rgba(16,185,129,.3);">
                <i class="fas fa-plus" style="margin-right:8px;"></i>Record Payment
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="lv-filter-bar">
        <div class="lv-filter-row">

            <div class="lv-filter-group lv-filter-group-grow">
                <label class="lv-filter-label">Search</label>
                <div class="lv-filter-icon-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" wire:model.live.debounce.400ms="search"
                        placeholder="Transaction ID, guest, booking #…"
                        class="lv-filter-input lv-filter-input-icon">
                    <div wire:loading.delay wire:target="search" class="lv-filter-spinner">
                        <svg class="animate-spin" style="width:14px;height:14px;color:#10b981;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="lv-filter-group" style="min-width:160px;">
                <label class="lv-filter-label">Method</label>
                <select wire:model.live="paymentMethod" class="lv-filter-select">
                    <option value="">All Methods</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="upi">UPI</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>

            <div class="lv-filter-group" style="min-width:135px;">
                <label class="lv-filter-label">From</label>
                <input type="date" wire:model.live="dateFrom" class="lv-filter-input">
            </div>

            <div class="lv-filter-group" style="min-width:135px;">
                <label class="lv-filter-label">To</label>
                <input type="date" wire:model.live="dateTo" class="lv-filter-input">
            </div>

            @if($search || $paymentMethod || $dateFrom || $dateTo)
            <div class="lv-filter-group" style="justify-content:flex-end;">
                <label class="lv-filter-label" style="opacity:0;">.</label>
                <button wire:click="clearFilters" class="lv-clear-btn">
                    <i class="fas fa-times" style="margin-right:5px;font-size:11px;"></i>Clear
                </button>
            </div>
            @endif
        </div>

        @if($search || $paymentMethod || $dateFrom || $dateTo)
        <div class="lv-filter-result" style="color:#059669;">
            <i class="fas fa-filter"></i>
            <strong>{{ $payments->total() }}</strong> result{{ $payments->total() != 1 ? 's' : '' }} found
        </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="lv-card" wire:loading.class="opacity-60">

        <div class="lv-card-header" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
            <div class="lv-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <i class="fas fa-credit-card"></i>
            </div>
            <div>
                <div class="lv-card-title">Payment Records <span>({{ $payments->total() }})</span></div>
                <div class="lv-card-subtitle">{{ ($search||$paymentMethod||$dateFrom||$dateTo) ? 'Filtered results' : 'All transactions' }}</div>
            </div>
        </div>

        <div class="lv-table-wrap">
            <table class="lv-table" style="min-width:720px;">
                <thead>
                    <tr>
                        <th class="lv-th">Transaction ID</th>
                        <th class="lv-th">Guest</th>
                        <th class="lv-th">Booking</th>
                        <th class="lv-th">Amount</th>
                        <th class="lv-th">Method</th>
                        <th class="lv-th">Type</th>
                        <th class="lv-th">Date</th>
                        <th class="lv-th lv-th-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    @php
                        $methodIcons = ['cash' => 'fas fa-money-bill-wave', 'card' => 'fas fa-credit-card', 'upi' => 'fas fa-mobile-alt', 'bank_transfer' => 'fas fa-university', 'cheque' => 'fas fa-file-alt'];
                        $mIcon = $methodIcons[$payment->payment_method] ?? 'fas fa-circle';
                        $mBadge = 'lv-badge-' . ($payment->payment_method === 'bank_transfer' ? 'bank' : ($payment->payment_method ?: 'gray'));
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $guestName = $payment->booking->customer->name ?? 'Unknown';
                        $ci = crc32($guestName) % 5;
                        if ($ci < 0) { $ci += 5; }
                    @endphp
                    <tr class="lv-row">
                        <td class="lv-td">
                            <span class="lv-mono" style="background:#f8fafc;padding:3px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                                {{ $payment->transaction_id ?? '—' }}
                            </span>
                        </td>
                        <td class="lv-td">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="lv-avatar" style="width:34px;height:34px;font-size:13px;background:{{ $gradients[$ci] }};">
                                    {{ strtoupper(substr($guestName, 0, 1)) }}
                                </div>
                                <span style="font-weight:700;font-size:14px;color:#1e293b;">{{ $guestName }}</span>
                            </div>
                        </td>
                        <td class="lv-td">
                            <a href="{{ route('bookings.show', $payment->booking_id) }}" class="lv-mono" style="color:#0891b2;text-decoration:none;">
                                {{ $payment->booking->booking_number ?? '—' }}
                            </a>
                        </td>
                        <td class="lv-td">
                            <div style="font-weight:900;font-size:16px;color:#16a34a;">₹{{ number_format($payment->amount) }}</div>
                        </td>
                        <td class="lv-td">
                            <span class="lv-badge {{ $mBadge }}">
                                <i class="{{ $mIcon }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </span>
                        </td>
                        <td class="lv-td">
                            <span class="lv-badge lv-badge-blue">{{ ucfirst($payment->payment_type) }}</span>
                        </td>
                        <td class="lv-td">
                            <div style="font-weight:600;font-size:13px;color:#374151;">{{ $payment->created_at->format('d M Y') }}</div>
                            <div class="lv-secondary">{{ $payment->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="lv-td lv-td-right">
                            <a href="{{ route('payments.show', $payment->id) }}" class="lv-action-btn lv-action-btn-green" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="lv-empty">
                            <div class="lv-empty-icon" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                                <i class="fas fa-credit-card" style="font-size:24px;color:#86efac;"></i>
                            </div>
                            <div class="lv-empty-title">No payment records found</div>
                            <div class="lv-empty-sub">{{ ($search||$paymentMethod||$dateFrom||$dateTo) ? 'Try adjusting your filters' : 'No transactions recorded yet' }}</div>
                            @if($search || $paymentMethod || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" class="lv-clear-btn">
                                <i class="fas fa-times" style="margin-right:5px;"></i>Clear Filters
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="lv-pagination">{{ $payments->links() }}</div>
    </div>

</div>
