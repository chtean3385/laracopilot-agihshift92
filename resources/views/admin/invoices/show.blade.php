@extends('layouts.admin')
@section('title','Invoice ' . $invoice->invoice_number)
@section('page-title','Invoice Details')
@section('page-subtitle',$invoice->invoice_number)
@section('content')
<div class="max-w-3xl space-y-5">
    @php
        // Compute pre-tax base from booking line items
        // invoice->total_amount is the POST-GST grand total set at checkout — do NOT add GST on top of it
        $_isWH2   = (bool)$invoice->booking->is_whole_hotel;
        $_overr2  = (bool)$invoice->booking->price_overridden;
        $_ext2    = $invoice->booking->extraCharges->sum('total_price');
        if ($_isWH2 || $_overr2) {
            $_roomPre = max(0, (float)$invoice->booking->total_amount - $_ext2);
        } else {
            $_roomPre = ($invoice->booking->nights ?? 0) * ($invoice->booking->room?->price_per_night ?? 0);
        }
        $_mealPre    = (float)($invoice->booking->meal_cost ?? 0);
        $_bedPre     = $invoice->booking->extra_beds > 0 ? (float)($invoice->booking->extra_bed_cost ?? 0) : 0;
        $_preBase    = $_roomPre + $_mealPre + $_bedPre + $_ext2;
        $previewGst     = ($settings && $settings->gst_number) ? round($_preBase * ((float)($settings->tax_rate ?? 0) / 100), 2) : 0;
        $previewGrand   = $_preBase + $previewGst;
        $previewBalance = max(0, $previewGrand - $invoice->paid_amount);
    @endphp
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('invoices.index') }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back</a>
        <div class="flex items-center gap-3 flex-wrap">
            @if($previewBalance > 0)
            <a href="{{ route('payments.create', ['booking_id' => $invoice->booking_id, 'amount' => $previewBalance]) }}"
               class="inline-flex items-center gap-2 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition-all shadow-sm" style="background: linear-gradient(135deg, #c9a96e, #b08d56);">
                <i class="fas fa-rupee-sign"></i>Collect ₹{{ number_format($previewBalance) }} Outstanding
            </a>
            @endif
            <a href="{{ route('invoices.print', $invoice->id) }}" class="btn-primary text-sm"><i class="fas fa-print mr-2"></i>Print Invoice</a>
            <a href="{{ route('invoices.print-gst', $invoice->id) }}" target="_blank"
               class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2.5 rounded-xl transition-all" style="border: 1px solid rgba(201,169,110,.2); background: rgba(201,169,110,.06); color: #b08d56;" onmouseover="this.style.background='rgba(201,169,110,.12)';" onmouseout="this.style.background='rgba(201,169,110,.06)';">
                <i class="fas fa-file-invoice"></i>GST Bill
            </a>
            <a href="{{ route('invoices.edit', $invoice->id) }}"
               class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2.5 rounded-xl transition-all" style="border: 1px solid rgba(201,169,110,.3); background: rgba(201,169,110,.08); color: #b08d56;" onmouseover="this.style.background='rgba(201,169,110,.15)';" onmouseout="this.style.background='rgba(201,169,110,.08)';"">
                <i class="fas fa-edit"></i>Edit Invoice
            </a>
            @canDo('invoices.delete')
            <button type="button" onclick="showDeleteModal()"
                class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2.5 rounded-xl border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-all">
                <i class="fas fa-trash"></i>Delete Invoice
            </button>
            @endCanDo
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-4 py-4 sm:px-8 sm:py-6 text-white">
            <div class="flex items-start justify-between flex-wrap gap-3">
                <div class="flex items-center gap-4">
                    @if($settings && $settings->logo_url)
                    <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center p-1 flex-shrink-0">
                        <img src="{{ $settings->logo_url }}" alt="Logo" class="max-w-full max-h-full object-contain">
                    </div>
                    @endif
                    <div>
                        <div class="text-2xl font-black">{{ $settings->resort_name ?? 'Azure Paradise Resort' }}</div>
                        @if($settings && $settings->tagline)<div class="text-xs font-semibold mb-0.5" style="color: #c9a96e;">{{ $settings->tagline }}</div>@endif
                        <div class="text-slate-400 text-sm mt-1">{{ $settings->address ?? '' }}</div>
                        <div class="text-slate-400 text-sm">{{ $settings->phone ?? '' }} • {{ $settings->email ?? '' }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black" style="color: #c9a96e;">INVOICE</div>
                    <div class="text-slate-300 font-mono">{{ $invoice->invoice_number }}</div>
                    <div class="text-slate-400 text-sm mt-1">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : now()->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="p-4 sm:p-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8 mb-6 sm:mb-8">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Bill To</p>
                    <p class="font-bold text-gray-800 text-lg">{{ $invoice->customer?->name ?? '(Deleted Guest)' }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->phone }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->email }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->city }}, {{ $invoice->customer->country }}</p>
                </div>
                <div class="sm:text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Booking Details</p>
                    <p class="font-mono font-bold" style="color: #c9a96e;">{{ $invoice->booking->booking_number }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->is_whole_hotel ? 'Whole Hotel / Villa' : ('Room ' . ($invoice->booking->room?->room_number ?? '')) }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->check_in_date->format('d M Y') }} → {{ $invoice->booking->check_out_date->format('d M Y') }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->nights }} night(s)</p>
                </div>
            </div>
            <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="w-full mb-6 min-w-[480px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Rate</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $invIsWH = (bool) $invoice->booking->is_whole_hotel;
                        $invExtraTotal = $invoice->booking->extraCharges->sum('total_price');
                        if ($invIsWH || $invoice->booking->price_overridden) {
                            $invRoomCost = max(0, (float)$invoice->booking->total_amount - $invExtraTotal);
                        } else {
                            $invRoomCost = ($invoice->booking->nights ?? 0) * ($invoice->booking->room?->price_per_night ?? 0);
                        }
                    @endphp
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 text-sm">
                            @if($invIsWH)
                                Whole Hotel / Villa — {{ \App\Models\Room::where('hotel_id', $invoice->booking->hotel_id)->count() }} room(s)
                                @if($invoice->booking->nights > 0)({{ $invoice->booking->nights }} night(s))@endif
                            @else
                                {{ ucfirst($invoice->booking->room?->type ?? '') }} Room {{ $invoice->booking->room?->room_number ?? '' }} - {{ $invoice->booking->room?->view ?? '' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">{{ $invoice->booking->nights ?: 1 }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            @if($invIsWH || $invoice->booking->price_overridden)₹{{ number_format($invRoomCost) }}@else₹{{ number_format($invoice->booking->room?->price_per_night ?? 0) }}@endif
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-right">₹{{ number_format($invRoomCost) }}</td>
                    </tr>
                    @if($invoice->booking->meal_cost > 0)
                    <tr class="border-b border-gray-100" style="background: rgba(201,169,110,.08);">
                        <td class="px-4 py-3 text-sm" style="color: #b08d56;">
                            <i class="fas fa-utensils mr-1" style="color: #c9a96e;"></i>Meal Plan —
                            @if($invoice->booking->meal_breakfast) Breakfast @endif
                            @if($invoice->booking->meal_lunch) Lunch @endif
                            @if($invoice->booking->meal_dinner) Dinner @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right" style="color: #b08d56;">{{ $invoice->booking->nights }} nights</td>
                        <td class="px-4 py-3 text-sm text-right" style="color: #b08d56;">—</td>
                        <td class="px-4 py-3 text-sm font-bold text-right" style="color: #b08d56;">₹{{ number_format($invoice->booking->meal_cost) }}</td>
                    </tr>
                    @endif
                    @if($invoice->booking->extra_beds > 0)
                    <tr class="border-b border-gray-100" style="background: rgba(122,138,154,.08);">
                        <td class="px-4 py-3 text-sm" style="color: #7a8a9a;">
                            <i class="fas fa-bed mr-1" style="color: #7a8a9a;"></i>Extra Beds × {{ $invoice->booking->extra_beds }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right" style="color: #7a8a9a;">{{ $invoice->booking->nights }} nights</td>
                        <td class="px-4 py-3 text-sm text-right" style="color: #7a8a9a;">₹{{ number_format($invoice->booking->room->extra_bed_price ?? 0) }}/bed</td>
                        <td class="px-4 py-3 text-sm font-bold text-right" style="color: #7a8a9a;">₹{{ number_format($invoice->booking->extra_bed_cost) }}</td>
                    </tr>
                    @endif
                    @if($invoice->booking->extraCharges->count() > 0)
                    <tr class="border-b border-gray-100" style="background: rgba(201,169,110,.08);">
                        <td colspan="4" class="px-4 py-2 text-xs font-bold uppercase tracking-wide" style="color: #b08d56;">
                            Extra Service Charge + Food
                        </td>
                    </tr>
                    @foreach($invoice->booking->extraCharges as $xCharge)
                    <tr class="border-b border-gray-100" style="background: rgba(201,169,110,.08);">
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <i class="fas fa-utensils mr-1" style="color: #c9a96e;"></i>{{ $xCharge->name }}
                            @if($xCharge->notes)<span class="text-gray-400 text-xs ml-1">({{ $xCharge->notes }})</span>@endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($xCharge->quantity, ($xCharge->quantity == intval($xCharge->quantity) ? 0 : 2)) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">₹{{ number_format($xCharge->unit_price) }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-right text-gray-800">₹{{ number_format($xCharge->total_price) }}</td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
            </div>{{-- /overflow-x:auto --}}
            @php
                // invoice->total_amount is POST-GST (set at checkout: base + tax).
                // Recompute the pre-tax subtotal from actual booking line items.
                $invMealCost     = (float)($invoice->booking->meal_cost ?? 0);
                $invExtraBedCost = $invoice->booking->extra_beds > 0 ? (float)($invoice->booking->extra_bed_cost ?? 0) : 0;
                // invRoomCost already computed above from nights×price (pre-tax)
                $invSubtotal     = $invRoomCost + $invMealCost + $invExtraBedCost + $invExtraTotal;
                $invFoodBase     = $invExtraTotal;
                $invRoomBase     = $invRoomCost + $invMealCost + $invExtraBedCost;
                $roomGst         = ($settings && $settings->gst_number) ? round($invRoomBase * ((float)($settings->tax_rate ?? 0) / 100), 2) : 0;
                $foodTaxRate     = $settings->food_tax_rate ?? 5;
                $foodGst         = ($settings && $settings->gst_number && $invFoodBase > 0) ? round($invFoodBase * ($foodTaxRate / 100), 2) : 0;
                $gstAmount       = $roomGst + $foodGst;
                $grandTotal      = $invSubtotal + $gstAmount;
                $displayBalance  = max(0, $grandTotal - $invoice->paid_amount);
                $overpayment     = max(0, $invoice->paid_amount - $grandTotal);
            @endphp
            <div class="flex justify-end">
                <div class="w-full sm:w-64 space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span>₹{{ number_format($invSubtotal) }}</span></div>
                    @if($settings && $settings->gst_number)
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Room GST ({{ $settings->tax_rate }}%)</span><span>₹{{ number_format($roomGst) }}</span></div>
                    @if($invFoodBase > 0)
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Food & Service GST ({{ $foodTaxRate }}%)</span><span>₹{{ number_format($foodGst) }}</span></div>
                    @endif
                    @endif
                    <div class="flex justify-between text-sm font-bold border-t pt-2"><span>Total</span><span>₹{{ number_format($grandTotal) }}</span></div>
                    <div class="flex justify-between text-sm" style="color: #c9a96e;"><span>Amount Paid</span><span>₹{{ number_format($invoice->paid_amount) }}</span></div>
                    @if($overpayment > 0)
                    <div class="flex justify-between text-sm px-2 py-1 rounded-lg" style="color: #b08d56; background: rgba(201,169,110,.08);">
                        <span class="font-medium">Overpayment / Credit Due</span>
                        <span class="font-bold">₹{{ number_format($overpayment) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-lg font-black border-t-2 border-gray-800 pt-2">
                        <span>Balance Due</span>
                        <span class="{{ $displayBalance > 0 ? 'text-red-500' : '' }}" style="{{ $displayBalance <= 0 ? 'color: #c9a96e;' : '' }}">₹{{ number_format($displayBalance) }}</span>
                    </div>
                </div>
            </div>
            @if($invoice->booking->special_requests)
            <div class="mt-6 pt-5 border-t border-gray-100">
                <div class="rounded-xl px-5 py-4" style="background: rgba(201,169,110,.08); border: 1px solid rgba(201,169,110,.15);">
                    <p class="text-xs font-bold uppercase mb-1" style="color: #b08d56;"><i class="fas fa-star mr-1"></i>Special Requests</p>
                    <p class="text-sm" style="color: #c9a96e;">{{ $invoice->booking->special_requests }}</p>
                </div>
            </div>
            @endif
            @if($invoice->booking->checkin_notes || $invoice->booking->checkout_notes)
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if($invoice->booking->checkin_notes)
                <div class="rounded-xl px-4 py-3" style="background: rgba(201,169,110,.08); border: 1px solid rgba(201,169,110,.15);">
                    <p class="text-xs font-bold uppercase mb-1" style="color: #b08d56;"><i class="fas fa-sign-in-alt mr-1"></i>Check-In Notes</p>
                    <p class="text-sm" style="color: #c9a96e;">{{ $invoice->booking->checkin_notes }}</p>
                </div>
                @endif
                @if($invoice->booking->checkout_notes)
                <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                    <p class="text-xs font-bold text-slate-600 uppercase mb-1"><i class="fas fa-sign-out-alt mr-1"></i>Check-Out Notes</p>
                    <p class="text-sm text-slate-600">{{ $invoice->booking->checkout_notes }}</p>
                </div>
                @endif
            </div>
            @endif
            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                @php $displayStatus = $displayBalance <= 0 ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid'); @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold {{ $displayStatus == 'paid' ? '' : ($displayStatus == 'partial' ? '' : 'bg-red-100 text-red-700') }}" style="{{ $displayStatus == 'paid' ? 'background: rgba(201,169,110,.15); color: #b08d56;' : ($displayStatus == 'partial' ? 'background: rgba(201,169,110,.15); color: #b08d56;' : '') }}">
                    {{ strtoupper($displayStatus) }}
                </span>
                @if($overpayment > 0)
                <p class="text-xs mt-2" style="color: #b08d56;">Guest has a credit of ₹{{ number_format($overpayment) }} — please process a refund.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@canDo('reports.view')
<div class="max-w-3xl mt-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <button type="button"
            onclick="var panel=this.closest('.bg-white').querySelector('#activityHistoryPanel');panel.classList.toggle('hidden');var icon=this.querySelector('.fa-chevron-down,.fa-chevron-up');icon.classList.toggle('fa-chevron-down');icon.classList.toggle('fa-chevron-up');"
            class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                    <i class="fas fa-history text-slate-500 text-sm"></i>
                </div>
                <div>
                    <span class="font-bold text-gray-800 text-sm">Activity History</span>
                    <span class="ml-2 text-xs bg-slate-100 text-slate-500 font-semibold px-2 py-0.5 rounded-full">{{ $activityLogs->count() }}</span>
                </div>
            </div>
            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
        </button>
        <div id="activityHistoryPanel" class="hidden border-t border-gray-100">
            @if($activityLogs->isEmpty())
            <div class="px-6 py-8 text-center text-gray-400 text-sm">
                <i class="fas fa-inbox text-2xl mb-2 block"></i>
                No activity recorded for this invoice yet.
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($activityLogs as $log)
                <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex-shrink-0 mt-0.5">
                        @php
                            $actionColor = match(strtolower($log->action)) {
                                'created'  => '',
                                'updated'  => '',
                                'deleted'  => 'bg-red-100 text-red-600',
                                default    => 'bg-gray-100 text-gray-500',
                            };
                            $actionStyle = match(strtolower($log->action)) {
                                'created'  => 'background: rgba(201,169,110,.1); color: #b08d56;',
                                'updated'  => 'background: rgba(122,138,154,.1); color: #7a8a9a;',
                                'deleted'  => '',
                                default    => '',
                            };
                            $actionIcon = match(strtolower($log->action)) {
                                'created'  => 'fa-plus',
                                'updated'  => 'fa-edit',
                                'deleted'  => 'fa-trash',
                                default    => 'fa-circle',
                            };
                        @endphp
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs {{ $actionColor }}" style="{{ $actionStyle }}">
                            <i class="fas {{ $actionIcon }}"></i>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-gray-800">{{ $log->user_name ?? 'System' }}</span>
                            @if($log->user_role)
                            <span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full font-medium">{{ $log->user_role }}</span>
                            @endif
                            <span class="text-xs font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $actionColor }}" style="{{ $actionStyle }}">{{ $log->action }}</span>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $log->description }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d M Y') }}</p>
                        <p class="text-xs text-gray-400 whitespace-nowrap">{{ $log->created_at->format('h:i A') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endCanDo

{{-- Delete Invoice Modal --}}
@canDo('invoices.delete')
<div id="deleteInvoiceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.6);">
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:460px;overflow:hidden;">
        {{-- Red header --}}
        <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-trash" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#fff;font-size:15px;">Delete Invoice</div>
                    <div style="font-size:12px;color:rgba(255,255,255,.75);">Invoice moves to trash — restorable within 30 days</div>
                </div>
            </div>
            <button type="button" onclick="hideDeleteModal()"
                style="width:30px;height:30px;background:rgba(255,255,255,.15);border:none;border-radius:8px;cursor:pointer;color:#fff;font-size:16px;display:flex;align-items:center;justify-content:center;">×</button>
        </div>
        {{-- Invoice details --}}
        <div style="padding:24px;">
            <div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:14px;padding:16px 18px;margin-bottom:20px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:2px;">Invoice #</div>
                        <div style="font-size:13px;font-weight:800;color:#1e293b;font-family:monospace;">{{ $invoice->invoice_number }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:2px;">Amount</div>
                        <div style="font-size:13px;font-weight:800;color:#dc2626;">₹{{ number_format($invoice->total_amount) }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:2px;">Guest</div>
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $invoice->customer?->name ?? '(Deleted Guest)' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:2px;">Date</div>
                        <div style="font-size:13px;font-weight:600;color:#475569;">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : '—' }}</div>
                    </div>
                    <div class="col-span-2" style="grid-column:span 2;">
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:2px;">Booking</div>
                        <div style="font-size:13px;font-weight:700;color:#1e293b;font-family:monospace;">{{ $invoice->booking->booking_number ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <p style="font-size:13px;color:#6b7280;margin-bottom:22px;line-height:1.6;">
                <i class="fas fa-exclamation-triangle" style="color:#f59e0b;margin-right:6px;"></i>
                Deleting this invoice will permanently remove it and reset the booking payment status to <strong>pending</strong>.
                <strong>This action is permanent and cannot be undone.</strong>
            </p>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="hideDeleteModal()"
                    style="padding:10px 20px;background:#f1f5f9;border:none;border-radius:11px;font-size:13px;font-weight:700;color:#64748b;cursor:pointer;">
                    Cancel
                </button>
                <form method="POST" action="{{ route('invoices.destroy', $invoice->id) }}" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit"
                        style="padding:10px 22px;background:linear-gradient(135deg,#dc2626,#b91c1c);border:none;border-radius:11px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;box-shadow:0 4px 12px rgba(220,38,38,.3);">
                        <i class="fas fa-trash" style="margin-right:6px;"></i>Yes, Delete Invoice
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function showDeleteModal() {
    document.getElementById('deleteInvoiceModal').classList.remove('hidden');
}
function hideDeleteModal() {
    document.getElementById('deleteInvoiceModal').classList.add('hidden');
}
// Auto-open modal if arriving from the list via #delete anchor
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#delete') showDeleteModal();
});
</script>
@endCanDo

@if(false)
{{-- removed: payment modals moved to checkout and payments/create --}}
<div id="upiQrModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between" style="background: linear-gradient(135deg, #1a2332, #2a3545);">
            <div class="flex items-center gap-3">
                <i class="fas fa-qrcode text-xl" style="color: #c9a96e;"></i>
                <div>
                    <h3 class="font-bold text-white">UPI Payment</h3>
                    <p class="text-xs" style="color: rgba(201,169,110,.7);">Scan to pay instantly</p>
                </div>
            </div>
            <button onclick="closeUpiModal()" class="text-white/70 hover:text-white transition-colors"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="p-6 text-center" id="upiQrBody">
            <div class="flex items-center justify-center h-40">
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-t-transparent" style="border-color: #c9a96e;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Razorpay Link Modal --}}
<div id="rzpModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between" style="background: linear-gradient(135deg, #1a2332, #2a3545);">
            <div class="flex items-center gap-3">
                <i class="fas fa-link text-xl" style="color: #c9a96e;"></i>
                <div>
                    <h3 class="font-bold text-white">Razorpay Payment Link</h3>
                    <p class="text-xs" style="color: rgba(201,169,110,.7);">Share link with guest for online payment</p>
                </div>
            </div>
            <button onclick="closeRzpModal()" class="text-white/70 hover:text-white transition-colors"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="p-6" id="rzpModalBody">
            <div class="flex items-center justify-center h-24">
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-blue-500 border-t-transparent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function showUpiQr(invoiceId) {
    document.getElementById('upiQrModal').classList.remove('hidden');
    document.getElementById('upiQrBody').innerHTML = '<div class="flex items-center justify-center h-40"><div class="animate-spin rounded-full h-10 w-10 border-2 border-violet-500 border-t-transparent"></div></div>';
    fetch('/payment-links/invoices/' + invoiceId + '/upi-qr', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.error) {
            document.getElementById('upiQrBody').innerHTML = '<div class="text-center py-8"><p class="text-red-500 font-medium"><i class="fas fa-exclamation-circle mr-2"></i>' + data.error + '</p><p class="text-gray-400 text-sm mt-2">Go to <a href="/payment-links/config" class="underline" style="color: #c9a96e;">Payment Links Config</a> to set up UPI.</p></div>';
            return;
        }
        document.getElementById('upiQrBody').innerHTML =
            '<img src="' + data.qr_url + '" alt="UPI QR" class="w-56 h-56 mx-auto rounded-xl border border-gray-200 shadow">' +
            '<p class="mt-4 text-lg font-black text-gray-800">₹' + parseFloat(data.amount).toLocaleString('en-IN', {minimumFractionDigits: 0}) + '</p>' +
            '<p class="text-sm text-gray-500 mt-1">' + (data.upi_name || '') + '</p>' +
            '<p class="text-xs text-gray-400 font-mono mt-0.5">' + data.upi_id + '</p>' +
            '<p class="text-xs text-gray-400 mt-4">Works with GPay, PhonePe, Paytm, any UPI app</p>';
    }).catch(() => {
        document.getElementById('upiQrBody').innerHTML = '<p class="text-center text-red-500 py-8">Failed to load QR. Please try again.</p>';
    });
}

function closeUpiModal() {
    document.getElementById('upiQrModal').classList.add('hidden');
}

function createRazorpayLink(invoiceId) {
    const existingUrl = '{{ $invoice->razorpay_payment_link_url }}';
    if (existingUrl) {
        showRzpLink(existingUrl);
        return;
    }
    document.getElementById('rzpModal').classList.remove('hidden');
    document.getElementById('rzpModalBody').innerHTML = '<div class="flex flex-col items-center justify-center py-8 gap-3"><div class="animate-spin rounded-full h-10 w-10 border-2 border-blue-500 border-t-transparent"></div><p class="text-gray-500 text-sm">Creating payment link…</p></div>';
    fetch('/payment-links/invoices/' + invoiceId + '/razorpay', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.error) {
            document.getElementById('rzpModalBody').innerHTML = '<div class="text-center py-6"><p class="text-red-500 font-medium"><i class="fas fa-exclamation-circle mr-2"></i>' + data.error + '</p><p class="text-gray-400 text-sm mt-2">Go to <a href="/payment-links/config" class="underline" style="color: #c9a96e;">Payment Links Config</a> to set up Razorpay.</p></div>';
            return;
        }
        document.getElementById('rzpBtnText').textContent = 'Razorpay Link';
        showRzpLink(data.link);
    }).catch(() => {
        document.getElementById('rzpModalBody').innerHTML = '<p class="text-center text-red-500 py-6">Failed to create link. Please try again.</p>';
    });
}

function showRzpLink(url) {
    document.getElementById('rzpModal').classList.remove('hidden');
    document.getElementById('rzpModalBody').innerHTML =
        '<div class="space-y-4">' +
        '<div class="rounded-xl p-4 text-center" style="background: rgba(201,169,110,.08); border: 1px solid rgba(201,169,110,.15);"><i class="fas fa-check-circle text-2xl mb-2" style="color: #c9a96e;"></i><p class="font-bold" style="color: #b08d56;">Payment Link Created!</p></div>' +
        '<div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex items-center gap-3">' +
        '<input type="text" value="' + url + '" id="rzpLinkInput" readonly class="flex-1 bg-transparent text-sm font-mono text-gray-700 outline-none truncate">' +
        '<button onclick="copyRzpLink()" class="flex-shrink-0 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors" style="background: linear-gradient(135deg, #c9a96e, #b08d56);"><i class="fas fa-copy mr-1"></i>Copy</button>' +
        '</div>' +
        '<a href="https://wa.me/?text=' + encodeURIComponent('Pay for your booking: ' + url) + '" target="_blank" class="flex items-center justify-center gap-2 text-white w-full py-2.5 rounded-xl font-semibold text-sm transition-colors" style="background: linear-gradient(135deg, #1a2332, #2a3545);">' +
        '<i class="fab fa-whatsapp text-lg"></i>Share via WhatsApp</a>' +
        '<button onclick="closeRzpModal()" class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">Close</button>' +
        '</div>';
}

function copyRzpLink() {
    const input = document.getElementById('rzpLinkInput');
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
        btn.style.background = 'linear-gradient(135deg, #c9a96e, #b08d56)';
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy mr-1"></i>Copy';
            btn.style.background = 'linear-gradient(135deg, #c9a96e, #b08d56)';
        }, 2000);
    });
}

function closeRzpModal() {
    document.getElementById('rzpModal').classList.add('hidden');
}

document.getElementById('upiQrModal').addEventListener('click', function(e) {
    if (e.target === this) closeUpiModal();
});
document.getElementById('rzpModal').addEventListener('click', function(e) {
    if (e.target === this) closeRzpModal();
});
</script>
@endif
@endsection
