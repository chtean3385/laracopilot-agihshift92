@extends('layouts.admin')
@section('title','Revenue Report')
@section('page-title','Revenue Report')
@section('page-subtitle','Payment collection analysis')
@section('content')
<div class="space-y-5">
    <form method="GET" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-wrap gap-4 items-end no-print">
        <div><label class="form-label">From</label><input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" class="form-input"></div>
        <div><label class="form-label">To</label><input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" class="form-input"></div>
        <button type="submit" class="btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
        <a href="{{ route('reports.revenue') }}" class="btn-secondary">Reset</a>
        <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('reports.revenue', array_merge(request()->only('date_from','date_to'), ['export'=>'pdf'])) }}"
               style="padding:8px 14px;background:#dc2626;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-file-pdf"></i>PDF
            </a>
            <a href="{{ route('reports.revenue', array_merge(request()->only('date_from','date_to'), ['export'=>'csv'])) }}"
               style="padding:8px 14px;background:#16a34a;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-file-csv"></i>CSV
            </a>
        </div>
    </form>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">Total Revenue</div>
            <div class="text-2xl font-black mt-1" style="color: #c9a96e;">₹{{ number_format($totalRevenue) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">Cash</div>
            <div class="text-2xl font-black text-gray-700 mt-1">₹{{ number_format($cashRevenue) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">Card</div>
            <div class="text-2xl font-black text-gray-700 mt-1">₹{{ number_format($cardRevenue) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">UPI</div>
            <div class="text-2xl font-black text-gray-700 mt-1">₹{{ number_format($upiRevenue) }}</div>
        </div>
    </div>
    {{-- Inline charts: daily revenue trend + payment method split --}}
    @php
        $rvLabels  = $dailyRevenue->keys()->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->values()->all();
        $rvValues  = $dailyRevenue->values()->map(fn($v) => (float)$v)->all();
        $rvPmLabels = ['Cash','Card','UPI'];
        $rvPmData   = [(float)$cashRevenue, (float)$cardRevenue, (float)$upiRevenue];
    @endphp
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100" style="padding:18px;">
            <div style="font-weight:800;color:#1e293b;font-size:14px;margin-bottom:6px;">Daily Revenue Trend</div>
            <div id="rvDailyChart" style="min-height:280px;"></div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100" style="padding:18px;">
            <div style="font-weight:800;color:#1e293b;font-size:14px;margin-bottom:6px;">Payment Method Split</div>
            <div id="rvPmChart" style="min-height:280px;"></div>
        </div>
    </div>
    <script>
    (function(){
        var labels = @json($rvLabels), data = @json($rvValues);
        var pmL = @json($rvPmLabels), pmD = @json($rvPmData);
        function fmt(v){v=Math.round(v||0); if(v>=1e7)return '₹'+(v/1e7).toFixed(2)+'Cr'; if(v>=1e5)return '₹'+(v/1e5).toFixed(2)+'L'; if(v>=1e3)return '₹'+(v/1e3).toFixed(1)+'K'; return '₹'+v;}
        function go(){
            new ApexCharts(document.querySelector('#rvDailyChart'), {
                chart:{type:'area',height:280,toolbar:{show:false},fontFamily:'Inter,sans-serif'},
                series:[{name:'Revenue',data:data}],
                xaxis:{categories:labels,labels:{style:{fontSize:'11px',colors:'#64748b'}}},
                yaxis:{labels:{formatter:fmt,style:{fontSize:'11px',colors:'#64748b'}}},
                stroke:{curve:'smooth',width:3},colors:['#10b981'],
                fill:{type:'gradient',gradient:{opacityFrom:.45,opacityTo:.05}},
                dataLabels:{enabled:false},grid:{borderColor:'#f1f5f9',strokeDashArray:4},
                tooltip:{y:{formatter:function(v){return '₹'+Number(v||0).toLocaleString('en-IN');}}},
                noData:{text:'No revenue in this period',style:{color:'#94a3b8',fontSize:'13px'}}
            }).render();
            if (pmD.reduce(function(a,b){return a+b;},0) > 0) {
                new ApexCharts(document.querySelector('#rvPmChart'), {
                    chart:{type:'donut',height:280,fontFamily:'Inter,sans-serif'},
                    series:pmD,labels:pmL,colors:['#64748b','#3b82f6','#8b5cf6'],
                    legend:{position:'bottom',fontSize:'12px'},
                    dataLabels:{enabled:true,formatter:function(v){return Math.round(v)+'%';}},
                    plotOptions:{pie:{donut:{size:'62%',labels:{show:true,total:{show:true,label:'Total',formatter:function(){return fmt(pmD.reduce(function(a,b){return a+b;},0));}}}}}}
                }).render();
            } else {
                document.querySelector('#rvPmChart').innerHTML = '<div style="padding:90px 0;text-align:center;color:#94a3b8;font-size:13px;">No payments in this period</div>';
            }
        }
        if (typeof ApexCharts !== 'undefined') go();
        else { var n=0,t=setInterval(function(){if(typeof ApexCharts!=='undefined'||++n>40){clearInterval(t);if(typeof ApexCharts!=='undefined')go();}},100); }
    })();
    </script>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-bold text-gray-800">Transactions ({{ $payments->count() }})</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $p)
                    @php $isWH = $p->booking && $p->booking->is_whole_hotel; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 text-xs text-gray-500">{{ $p->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-sm font-medium">{{ $p->booking->customer->name ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-xs font-mono text-[#c9a96e]">{{ $p->booking->booking_number ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-sm">
                            @if($isWH)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold" style="background: rgba(201,169,110,.15); color: #b08d56;">
                                    <i class="fas fa-hotel" style="font-size:10px;"></i> Whole Hotel
                                </span>
                            @else
                                {{ $p->booking->room?->room_number ?? '—' }}
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm">{{ ucfirst($p->payment_method) }}</td>
                        <td class="px-6 py-3 text-sm font-bold text-right" style="color: #c9a96e;">₹{{ number_format($p->amount) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No transactions in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
