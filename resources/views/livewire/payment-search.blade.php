<div>

    {{-- Stats row --}}
    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:16px;margin-bottom:20px;align-items:stretch;" class="pay-stats-grid">
        <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:20px;padding:22px 26px;box-shadow:0 4px 16px rgba(16,185,129,.25);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,.1);"></div>
            <div style="font-size:12px;font-weight:600;color:rgba(255,255,255,.75);margin-bottom:6px;letter-spacing:.04em;">TOTAL REVENUE</div>
            <div style="font-size:26px;font-weight:900;color:#fff;line-height:1;">₹{{ number_format($totalRevenue) }}</div>
            <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:4px;">All completed payments</div>
        </div>
        <div style="background:#fff;border-radius:20px;padding:22px 26px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            <div style="font-size:12px;font-weight:600;color:#94a3b8;margin-bottom:6px;letter-spacing:.04em;">TRANSACTIONS</div>
            <div style="font-size:26px;font-weight:900;color:#1e293b;line-height:1;">{{ $payments->total() }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">
                <span wire:loading.remove>{{ ($search||$paymentMethod||$dateFrom||$dateTo) ? 'Filtered results' : 'All records' }}</span>
                <span wire:loading style="color:#10b981;"><i class="fas fa-circle-notch fa-spin"></i> Updating…</span>
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:flex-end;">
            <a href="{{ route('payments.create') }}" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:12px 22px;border-radius:14px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 4px 12px rgba(16,185,129,.3);transition:all .15s;white-space:nowrap;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform='translateY(0)'">
                <i class="fas fa-plus"></i> Record Payment
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div style="background:#fff;border-radius:20px;padding:18px 22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:20px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">

            <div style="flex:1;min-width:200px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Search</div>
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;"></i>
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Transaction ID, guest, booking #…"
                        style="width:100%;padding:9px 36px 9px 36px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                        onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
                    <div wire:loading.delay wire:target="search" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);">
                        <svg class="animate-spin" style="width:14px;height:14px;color:#10b981;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div style="min-width:160px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Method</div>
                <select wire:model.live="paymentMethod"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;background:#fff;cursor:pointer;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
                    <option value="">All Methods</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="upi">UPI</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>

            <div style="min-width:140px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">From</div>
                <input type="date" wire:model.live="dateFrom"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <div style="min-width:140px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">To</div>
                <input type="date" wire:model.live="dateTo"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            @if($search || $paymentMethod || $dateFrom || $dateTo)
            <div>
                <div style="font-size:11px;color:transparent;margin-bottom:6px;">.</div>
                <button wire:click="clearFilters"
                    style="padding:9px 18px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;"
                    onmouseenter="this.style.borderColor='#94a3b8';this.style.background='#f8fafc'" onmouseleave="this.style.borderColor='#e2e8f0';this.style.background='#fff'">
                    <i class="fas fa-times" style="margin-right:5px;font-size:11px;"></i>Clear
                </button>
            </div>
            @endif
        </div>

        @if($search || $paymentMethod || $dateFrom || $dateTo)
        <div style="margin-top:12px;font-size:12px;color:#059669;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-filter"></i>
            <strong>{{ $payments->total() }}</strong> result{{ $payments->total() != 1 ? 's' : '' }} found
        </div>
        @endif
    </div>

    {{-- Table --}}
    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;" wire:loading.class="opacity-60">

        <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#f0fdf4,#dcfce7);display:flex;align-items:center;gap:12px;">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-credit-card" style="color:#fff;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-weight:800;color:#1e293b;font-size:15px;">Payment Records <span style="font-size:13px;font-weight:500;color:#94a3b8;">({{ $payments->total() }})</span></div>
                <div style="font-size:11px;color:#94a3b8;">{{ ($search||$paymentMethod||$dateFrom||$dateTo) ? 'Filtered results' : 'All transactions' }}</div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:700px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Transaction ID</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Guest</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Booking</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Amount</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Method</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Type</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Date</th>
                        <th style="text-align:right;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    @php
                        $methodIcons = ['cash' => 'fas fa-money-bill-wave', 'card' => 'fas fa-credit-card', 'upi' => 'fas fa-mobile-alt', 'bank_transfer' => 'fas fa-university', 'cheque' => 'fas fa-file-alt'];
                        $methodColors = ['cash' => '#16a34a', 'card' => '#2563eb', 'upi' => '#7c3aed', 'bank_transfer' => '#0891b2', 'cheque' => '#d97706'];
                        $methodBgs = ['cash' => '#dcfce7', 'card' => '#dbeafe', 'upi' => '#ede9fe', 'bank_transfer' => '#ecfeff', 'cheque' => '#fef3c7'];
                        $mIcon = $methodIcons[$payment->payment_method] ?? 'fas fa-circle';
                        $mClr = $methodColors[$payment->payment_method] ?? '#475569';
                        $mBg = $methodBgs[$payment->payment_method] ?? '#f1f5f9';
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $guestName = $payment->booking->customer->name ?? 'Unknown';
                        $ci = crc32($guestName) % 5;
                        if ($ci < 0) { $ci += 5; }
                    @endphp
                    <tr style="border-top:1px solid #f8fafc;transition:background .12s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">

                        <td style="padding:14px 18px;">
                            <span style="font-family:monospace;font-size:11px;font-weight:700;color:#64748b;background:#f8fafc;padding:3px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                                {{ $payment->transaction_id ?? '—' }}
                            </span>
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;background:{{ $gradients[$ci] }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.1);">
                                    {{ strtoupper(substr($guestName, 0, 1)) }}
                                </div>
                                <span style="font-weight:700;color:#1e293b;font-size:13px;">{{ $guestName }}</span>
                            </div>
                        </td>

                        <td style="padding:14px 18px;">
                            <a href="{{ route('bookings.show', $payment->booking_id) }}" style="font-family:monospace;font-size:12px;font-weight:700;color:#0891b2;text-decoration:none;" onmouseenter="this.style.textDecoration='underline'" onmouseleave="this.style.textDecoration='none'">
                                {{ $payment->booking->booking_number ?? '—' }}
                            </a>
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="font-weight:900;color:#16a34a;font-size:15px;">₹{{ number_format($payment->amount) }}</div>
                        </td>

                        <td style="padding:14px 18px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:{{ $mBg }};color:{{ $mClr }};border-radius:20px;font-size:11px;font-weight:700;">
                                <i class="{{ $mIcon }}" style="font-size:10px;"></i>
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </span>
                        </td>

                        <td style="padding:14px 18px;">
                            <span style="display:inline-flex;align-items:center;padding:4px 12px;background:#eff6ff;color:#2563eb;border-radius:20px;font-size:11px;font-weight:700;">
                                {{ ucfirst($payment->payment_type) }}
                            </span>
                        </td>

                        <td style="padding:14px 18px;font-size:12px;color:#64748b;white-space:nowrap;">
                            <div style="font-weight:600;color:#374151;">{{ $payment->created_at->format('d M Y') }}</div>
                            <div style="font-size:11px;color:#94a3b8;">{{ $payment->created_at->format('h:i A') }}</div>
                        </td>

                        <td style="padding:14px 18px;text-align:right;">
                            <a href="{{ route('payments.show', $payment->id) }}" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:#f0fdf4;color:#16a34a;border-radius:9px;text-decoration:none;transition:background .12s;" title="View" onmouseenter="this.style.background='#dcfce7'" onmouseleave="this.style.background='#f0fdf4'">
                                <i class="fas fa-eye" style="font-size:11px;"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding:60px 24px;text-align:center;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                                <i class="fas fa-credit-card" style="font-size:24px;color:#86efac;"></i>
                            </div>
                            <div style="font-size:15px;font-weight:700;color:#475569;margin-bottom:6px;">No payment records found</div>
                            <div style="font-size:13px;color:#94a3b8;margin-bottom:16px;">{{ ($search||$paymentMethod||$dateFrom||$dateTo) ? 'Try adjusting your filters' : 'No transactions recorded yet' }}</div>
                            @if($search || $paymentMethod || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#f1f5f9;border:none;border-radius:12px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 22px;border-top:1px solid #f8fafc;">{{ $payments->links() }}</div>
    </div>

</div>

<style>
@media (max-width: 768px) {
    .pay-stats-grid { grid-template-columns: 1fr 1fr !important; }
    .pay-stats-grid > div:last-child { grid-column: span 2; justify-content: flex-start !important; }
}
</style>
