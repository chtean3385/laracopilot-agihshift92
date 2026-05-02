<div>

    {{-- Filter bar --}}
    <div class="lv-filter-bar">
        <div class="lv-filter-row">

            <div class="lv-filter-group lv-filter-group-grow">
                <label class="lv-filter-label">Search</label>
                <div class="lv-filter-icon-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" wire:model.live.debounce.400ms="search"
                        placeholder="Invoice #, guest, room, amount…"
                        class="lv-filter-input lv-filter-input-icon" style="border-color:{{ '' }};">
                    <div wire:loading.delay wire:target="search" class="lv-filter-spinner">
                        <svg class="animate-spin" style="width:14px;height:14px;color:#7c3aed;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="lv-filter-group" style="min-width:150px;">
                <label class="lv-filter-label">Status</label>
                <select wire:model.live="status" class="lv-filter-select">
                    <option value="">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="unpaid">Unpaid</option>
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

            @if($search || $status || $dateFrom || $dateTo)
            <div class="lv-filter-group" style="justify-content:flex-end;">
                <label class="lv-filter-label" style="opacity:0;">.</label>
                <button wire:click="clearFilters" class="lv-clear-btn">
                    <i class="fas fa-times" style="margin-right:5px;font-size:11px;"></i>Clear
                </button>
            </div>
            @endif
        </div>

        @if($search || $status || $dateFrom || $dateTo)
        <div class="lv-filter-result" style="color:#7c3aed;">
            <i class="fas fa-filter"></i>
            <strong>{{ $invoices->total() }}</strong> result{{ $invoices->total() != 1 ? 's' : '' }} found
        </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="lv-card" wire:loading.class="opacity-60">

        <div class="lv-card-header" style="background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
            <div class="lv-card-icon" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <div class="lv-card-title">All Invoices <span>({{ $invoices->total() }})</span></div>
                <div class="lv-card-subtitle">{{ ($search||$status||$dateFrom||$dateTo) ? 'Filtered results' : 'All generated invoices' }}</div>
            </div>
        </div>

        <div class="lv-table-wrap">
            <table class="lv-table" style="min-width:760px;">
                <thead>
                    <tr>
                        <th class="lv-th">Invoice #</th>
                        <th class="lv-th">Guest</th>
                        <th class="lv-th">Room</th>
                        <th class="lv-th">Total</th>
                        <th class="lv-th">Paid</th>
                        <th class="lv-th">Balance</th>
                        <th class="lv-th">Status</th>
                        <th class="lv-th">Date</th>
                        <th class="lv-th lv-th-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php
                        $sMap = ['paid' => 'lv-badge-green', 'partial' => 'lv-badge-amber', 'unpaid' => 'lv-badge-red'];
                        $sCls = $sMap[$invoice->status] ?? 'lv-badge-gray';
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $ci = crc32($invoice->customer?->name ?? 'G') % 5;
                        if ($ci < 0) { $ci += 5; }
                    @endphp
                    <tr class="lv-row">
                        <td class="lv-td">
                            <a href="{{ route('invoices.show', $invoice->id) }}" class="lv-mono" style="color:#7c3aed;text-decoration:none;">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td class="lv-td">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="lv-avatar" style="width:36px;height:36px;font-size:14px;background:{{ $gradients[$ci] }};">
                                    {{ strtoupper(substr($invoice->customer?->name ?? 'G', 0, 1)) }}
                                </div>
                                @if($invoice->customer)
                                <a href="{{ route('customers.show', $invoice->customer->id) }}" class="lv-name-link">{{ $invoice->customer->name }}</a>
                                @else
                                <span class="lv-name-link" style="color:#94a3b8;">(Deleted Guest)</span>
                                @endif
                            </div>
                        </td>
                        <td class="lv-td">
                            <div class="lv-room-pill">
                                <span class="lv-room-pill-label">RM</span>
                                <span class="lv-room-pill-num">{{ $invoice->booking->room->room_number ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="lv-td">
                            <div style="font-weight:800;font-size:15px;color:#1e293b;">₹{{ number_format($invoice->total_amount) }}</div>
                        </td>
                        <td class="lv-td">
                            <div style="font-weight:700;font-size:14px;color:#16a34a;">₹{{ number_format($invoice->paid_amount) }}</div>
                        </td>
                        <td class="lv-td">
                            <div style="font-weight:800;font-size:15px;color:{{ $invoice->balance > 0 ? '#e11d48' : '#16a34a' }};">
                                ₹{{ number_format($invoice->balance) }}
                            </div>
                        </td>
                        <td class="lv-td">
                            <span class="lv-badge {{ $sCls }}">{{ ucfirst($invoice->status) }}</span>
                        </td>
                        <td class="lv-td">
                            <div style="font-size:13px;color:#64748b;">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : '—' }}</div>
                        </td>
                        <td class="lv-td lv-td-right">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <a href="{{ route('invoices.show', $invoice->id) }}" class="lv-action-btn lv-action-btn-purple" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="lv-action-btn lv-action-btn-gray" title="Print Invoice">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="{{ route('invoices.print-gst', $invoice->id) }}" target="_blank"
                                   class="lv-action-btn" style="background:#ede9fe;color:#7c3aed;" title="GST Bill">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                                <a href="{{ route('invoices.edit', $invoice->id) }}"
                                   class="lv-action-btn" style="background:#fef3c7;color:#d97706;" title="Edit Invoice">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="lv-empty">
                            <div class="lv-empty-icon" style="background:linear-gradient(135deg,#faf5ff,#ede9fe);">
                                <i class="fas fa-file-invoice" style="font-size:24px;color:#c4b5fd;"></i>
                            </div>
                            <div class="lv-empty-title">No invoices found</div>
                            <div class="lv-empty-sub">{{ ($search||$status||$dateFrom||$dateTo) ? 'Try adjusting your filters' : 'Invoices are generated at check-out' }}</div>
                            @if($search || $status || $dateFrom || $dateTo)
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
        <div class="lv-pagination">{{ $invoices->links() }}</div>
    </div>

</div>
