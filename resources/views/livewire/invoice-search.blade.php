<div>

    {{-- Filter bar --}}
    <div style="background:#fff;border-radius:20px;padding:18px 22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:20px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">

            <div style="flex:1;min-width:200px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Search</div>
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;"></i>
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Invoice #, guest, room, amount…"
                        style="width:100%;padding:9px 36px 9px 36px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                        onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                    <div wire:loading.delay wire:target="search" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);">
                        <svg class="animate-spin" style="width:14px;height:14px;color:#7c3aed;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div style="min-width:150px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Status</div>
                <select wire:model.live="status"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;background:#fff;cursor:pointer;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                    <option value="">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </div>

            <div style="min-width:140px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">From</div>
                <input type="date" wire:model.live="dateFrom"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <div style="min-width:140px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">To</div>
                <input type="date" wire:model.live="dateTo"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            @if($search || $status || $dateFrom || $dateTo)
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

        @if($search || $status || $dateFrom || $dateTo)
        <div style="margin-top:12px;font-size:12px;color:#7c3aed;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-filter"></i>
            <span wire:loading.remove wire:target="search,status,dateFrom,dateTo">
                <strong>{{ $invoices->total() }}</strong> result{{ $invoices->total() != 1 ? 's' : '' }} found
            </span>
            <span wire:loading wire:target="search,status,dateFrom,dateTo" style="color:#7c3aed;">
                <i class="fas fa-circle-notch fa-spin"></i> Updating…
            </span>
        </div>
        @endif
    </div>

    {{-- Table --}}
    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;" wire:loading.class="opacity-60">

        <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#faf5ff,#f5f3ff);display:flex;align-items:center;gap:12px;">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-file-invoice" style="color:#fff;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-weight:800;color:#1e293b;font-size:15px;">All Invoices <span style="font-size:13px;font-weight:500;color:#94a3b8;">({{ $invoices->total() }})</span></div>
                <div style="font-size:11px;color:#94a3b8;">{{ ($search||$status||$dateFrom||$dateTo) ? 'Filtered results' : 'All generated invoices' }}</div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:760px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Invoice #</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Guest</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Room</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Total</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Paid</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Balance</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Status</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Date</th>
                        <th style="text-align:right;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php
                        $sMap = ['paid' => ['#dcfce7','#15803d'], 'partial' => ['#fef3c7','#92400e'], 'unpaid' => ['#fee2e2','#b91c1c']];
                        [$sBg, $sClr] = $sMap[$invoice->status] ?? ['#f3f4f6','#374151'];
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $ci = crc32($invoice->customer->name) % 5;
                        if ($ci < 0) { $ci += 5; }
                    @endphp
                    <tr style="border-top:1px solid #f8fafc;transition:background .12s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">

                        <td style="padding:14px 18px;">
                            <a href="{{ route('invoices.show', $invoice->id) }}" style="font-family:monospace;font-size:12px;font-weight:700;color:#7c3aed;text-decoration:none;" onmouseenter="this.style.textDecoration='underline'" onmouseleave="this.style.textDecoration='none'">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;background:{{ $gradients[$ci] }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.1);">
                                    {{ strtoupper(substr($invoice->customer->name, 0, 1)) }}
                                </div>
                                <a href="{{ route('customers.show', $invoice->customer->id) }}" style="font-weight:700;color:#1e293b;font-size:13px;text-decoration:none;" onmouseenter="this.style.color='#7c3aed'" onmouseleave="this.style.color='#1e293b'">{{ $invoice->customer->name }}</a>
                            </div>
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="display:inline-flex;flex-direction:column;align-items:center;justify-content:center;width:46px;height:40px;background:linear-gradient(135deg,#0f172a,#1e3a5f);border-radius:10px;">
                                <div style="font-size:9px;color:rgba(255,255,255,.5);font-weight:600;line-height:1;letter-spacing:.03em;">RM</div>
                                <div style="font-size:13px;font-weight:900;color:#fff;line-height:1.1;">{{ $invoice->booking->room->room_number ?? '—' }}</div>
                            </div>
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="font-weight:800;color:#1e293b;font-size:14px;">₹{{ number_format($invoice->total_amount) }}</div>
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="font-weight:700;color:#16a34a;font-size:13px;">₹{{ number_format($invoice->paid_amount) }}</div>
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="font-weight:800;font-size:14px;color:{{ $invoice->balance > 0 ? '#e11d48' : '#16a34a' }};">₹{{ number_format($invoice->balance) }}</div>
                        </td>

                        <td style="padding:14px 18px;">
                            <span style="display:inline-flex;align-items:center;padding:4px 12px;background:{{ $sBg }};color:{{ $sClr }};border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>

                        <td style="padding:14px 18px;font-size:12px;color:#94a3b8;white-space:nowrap;">
                            {{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : '—' }}
                        </td>

                        <td style="padding:14px 18px;">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <a href="{{ route('invoices.show', $invoice->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#faf5ff;color:#7c3aed;border-radius:9px;text-decoration:none;transition:background .12s;" title="View" onmouseenter="this.style.background='#ede9fe'" onmouseleave="this.style.background='#faf5ff'">
                                    <i class="fas fa-eye" style="font-size:11px;"></i>
                                </a>
                                <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#475569;border-radius:9px;text-decoration:none;transition:background .12s;" title="Print" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#f8fafc'">
                                    <i class="fas fa-print" style="font-size:11px;"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="padding:60px 24px;text-align:center;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#faf5ff,#ede9fe);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                                <i class="fas fa-file-invoice" style="font-size:24px;color:#c4b5fd;"></i>
                            </div>
                            <div style="font-size:15px;font-weight:700;color:#475569;margin-bottom:6px;">No invoices found</div>
                            <div style="font-size:13px;color:#94a3b8;">{{ ($search||$status||$dateFrom||$dateTo) ? 'Try adjusting your filters' : 'Invoices are generated at check-out' }}</div>
                            @if($search || $status || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" style="margin-top:14px;display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#f1f5f9;border:none;border-radius:12px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 22px;border-top:1px solid #f8fafc;">{{ $invoices->links() }}</div>
    </div>

</div>
