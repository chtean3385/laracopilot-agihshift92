@extends('layouts.admin')
@section('title','Occupancy Report')
@section('page-title','Occupancy Report')
@section('page-subtitle','Room utilization analysis')
@section('content')
<div class="space-y-5">
    <form method="GET" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-wrap gap-4 items-end no-print">
        <div><label class="form-label">From</label><input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" class="form-input"></div>
        <div><label class="form-label">To</label><input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" class="form-input"></div>
        <button type="submit" class="btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
        <a href="{{ route('reports.occupancy') }}" class="btn-secondary">Reset</a>
        <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('reports.occupancy', array_merge(request()->only('date_from','date_to'), ['export'=>'pdf'])) }}"
               style="padding:8px 14px;background:#dc2626;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-file-pdf"></i>PDF
            </a>
            <a href="{{ route('reports.occupancy', array_merge(request()->only('date_from','date_to'), ['export'=>'csv'])) }}"
               style="padding:8px 14px;background:#16a34a;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-file-csv"></i>CSV
            </a>
        </div>
    </form>
    {{-- Inline charts: bookings per room + bookings by type --}}
    @php
        $occRoomLabels = $roomStats->pluck('room_number')->map(fn($r)=>(string)$r)->all();
        $occRoomData   = $roomStats->pluck('bookings_count')->map(fn($v)=>(int)$v)->all();
        $occTypeLabels = $bookingsByType->keys()->map(fn($k)=>ucfirst((string)($k ?: 'Other')))->all();
        $occTypeData   = $bookingsByType->values()->map(fn($v)=>(int)$v)->all();
    @endphp
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100" style="padding:18px;">
            <div style="font-weight:800;color:#1e293b;font-size:14px;margin-bottom:6px;">Bookings per Room</div>
            <div id="occRoomChart" style="min-height:280px;"></div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100" style="padding:18px;">
            <div style="font-weight:800;color:#1e293b;font-size:14px;margin-bottom:6px;">Bookings by Room Type</div>
            <div id="occTypeChart" style="min-height:280px;"></div>
        </div>
    </div>
    <script>
    (function(){
        var rl=@json($occRoomLabels), rd=@json($occRoomData), tl=@json($occTypeLabels), td=@json($occTypeData);
        function go(){
            new ApexCharts(document.querySelector('#occRoomChart'), {
                chart:{type:'bar',height:280,toolbar:{show:false},fontFamily:'Inter,sans-serif'},
                series:[{name:'Bookings',data:rd}],
                xaxis:{categories:rl,labels:{style:{fontSize:'10px',colors:'#64748b'},rotate:-45}},
                yaxis:{labels:{style:{fontSize:'11px',colors:'#64748b'}}},
                colors:['#0ea5e9'],plotOptions:{bar:{borderRadius:5,columnWidth:'60%'}},
                dataLabels:{enabled:false},grid:{borderColor:'#f1f5f9',strokeDashArray:4},
                noData:{text:'No data',style:{color:'#94a3b8',fontSize:'13px'}}
            }).render();
            if (td.reduce(function(a,b){return a+b;},0) > 0){
                new ApexCharts(document.querySelector('#occTypeChart'), {
                    chart:{type:'donut',height:280,fontFamily:'Inter,sans-serif'},
                    series:td,labels:tl,colors:['#f43f5e','#fb923c','#facc15','#84cc16','#06b6d4','#8b5cf6','#ec4899','#64748b'],
                    legend:{position:'bottom',fontSize:'12px'},
                    dataLabels:{enabled:true,formatter:function(v){return Math.round(v)+'%';}},
                    plotOptions:{pie:{donut:{size:'62%'}}}
                }).render();
            } else {
                document.querySelector('#occTypeChart').innerHTML='<div style="padding:90px 0;text-align:center;color:#94a3b8;font-size:13px;">No bookings in this period</div>';
            }
        }
        if (typeof ApexCharts !== 'undefined') go();
        else { var n=0,t=setInterval(function(){if(typeof ApexCharts!=='undefined'||++n>40){clearInterval(t);if(typeof ApexCharts!=='undefined')go();}},100); }
    })();
    </script>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Room Booking Count</h3>
            <span class="text-xs text-gray-400">Period: {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Pricing</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Period Status</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Bookings in Period</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($roomStats as $room)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 font-bold text-gray-800">{{ $room->room_number }}</td>
                        <td class="px-6 py-3 text-sm">{{ ucfirst($room->type) }}</td>
                        <td class="px-6 py-3 text-sm">
                            @if($room->pricing_type === 'per_slot')
                                <span class="inline-flex items-center gap-1 text-violet-600">
                                    <i class="fas fa-clock text-xs"></i> Slot
                                </span>
                            @elseif($room->pricing_type === 'per_hour')
                                <span class="inline-flex items-center gap-1 text-blue-600">
                                    <i class="fas fa-hourglass-half text-xs"></i> Hourly
                                </span>
                            @else
                                <span class="text-gray-500">Nightly</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            @if($room->bookings_count > 0)
                                <span class="badge-red">Booked</span>
                            @else
                                <span class="badge-green">Free</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-gray-700">{{ $room->bookings_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
