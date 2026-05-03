@extends('layouts.admin')
@section('title','Performance Analysis')
@section('page-title','Performance Analysis')
@section('page-subtitle','Monthly trends, channel mix and improvement insights')
@section('content')

<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Back link --}}
    <div>
        <a href="{{ route('reports.index') }}" style="color:#6366f1;font-size:13px;font-weight:600;text-decoration:none;">← Back to Reports</a>
    </div>

    {{-- KPI cards: ADR / RevPAR / 12m revenue / 12m avg occupancy --}}
    @php
        $rev12m   = array_sum($monthRevenue);
        $occ12m   = count($monthOccupancy) ? round(array_sum($monthOccupancy) / count($monthOccupancy), 1) : 0;
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;"><i class="fas fa-coins" style="color:#fff;font-size:13px;"></i></div>
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">12-Month Revenue</div>
            </div>
            <div style="font-size:22px;font-weight:900;color:#1e293b;">₹{{ number_format($rev12m) }}</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#0ea5e9,#0284c7);display:flex;align-items:center;justify-content:center;"><i class="fas fa-bed" style="color:#fff;font-size:13px;"></i></div>
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Avg Occupancy (12m)</div>
            </div>
            <div style="font-size:22px;font-weight:900;color:#1e293b;">{{ $occ12m }}%</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;"><i class="fas fa-tag" style="color:#fff;font-size:13px;"></i></div>
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">ADR (30d)</div>
            </div>
            <div style="font-size:22px;font-weight:900;color:#1e293b;">₹{{ number_format($adr) }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $totalRoomNights }} room-nights</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#5b21b6);display:flex;align-items:center;justify-content:center;"><i class="fas fa-chart-line" style="color:#fff;font-size:13px;"></i></div>
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">RevPAR (30d)</div>
            </div>
            <div style="font-size:22px;font-weight:900;color:#1e293b;">₹{{ number_format($revpar) }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">per available room / day</div>
        </div>
    </div>

    {{-- Monthly Revenue + Occupancy --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#10b981,#0ea5e9);display:flex;align-items:center;justify-content:center;"><i class="fas fa-chart-column" style="color:#fff;font-size:13px;"></i></div>
            <div>
                <div style="font-size:15px;font-weight:800;color:#1e293b;">Monthly Revenue & Occupancy</div>
                <div style="font-size:12px;color:#94a3b8;">Last 12 months</div>
            </div>
        </div>
        <div id="perfMonthlyChart" style="min-height:340px;"></div>
    </div>

    {{-- Two-column row: room-type donut + source donut --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#f43f5e,#e11d48);display:flex;align-items:center;justify-content:center;"><i class="fas fa-door-open" style="color:#fff;font-size:13px;"></i></div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">Bookings by Room Type</div>
                    <div style="font-size:12px;color:#94a3b8;">Last 90 days</div>
                </div>
            </div>
            @if(empty($roomTypeData))
                <div style="padding:48px 0;text-align:center;color:#94a3b8;font-size:13px;">No bookings in the last 90 days.</div>
            @else
                <div id="perfRoomTypeChart" style="min-height:300px;"></div>
            @endif
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;"><i class="fas fa-share-nodes" style="color:#fff;font-size:13px;"></i></div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">Booking Source Mix</div>
                    <div style="font-size:12px;color:#94a3b8;">Last 90 days</div>
                </div>
            </div>
            @if(empty($sourceCounts))
                <div style="padding:48px 0;text-align:center;color:#94a3b8;font-size:13px;">No booking-source data yet.</div>
            @else
                <div id="perfSourceChart" style="min-height:300px;"></div>
            @endif
        </div>
    </div>

    {{-- Two-column row: day-of-week + payment method --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#0ea5e9,#0369a1);display:flex;align-items:center;justify-content:center;"><i class="fas fa-calendar-day" style="color:#fff;font-size:13px;"></i></div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">Revenue by Day of Week</div>
                    <div style="font-size:12px;color:#94a3b8;">Last 90 days · find your strongest days</div>
                </div>
            </div>
            <div id="perfDowChart" style="min-height:280px;"></div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;"><i class="fas fa-money-bill-wave" style="color:#fff;font-size:13px;"></i></div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">Revenue by Payment Method</div>
                    <div style="font-size:12px;color:#94a3b8;">Last 90 days</div>
                </div>
            </div>
            @if(empty($pmAmounts))
                <div style="padding:48px 0;text-align:center;color:#94a3b8;font-size:13px;">No payments recorded.</div>
            @else
                <div id="perfPmChart" style="min-height:280px;"></div>
            @endif
        </div>
    </div>

    {{-- Insights panel --}}
    <div style="background:linear-gradient(135deg,#fefce8,#fff7ed);border:1px solid #fde68a;border-radius:16px;padding:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(245,158,11,.3);"><i class="fas fa-lightbulb" style="color:#fff;font-size:15px;"></i></div>
            <div>
                <div style="font-size:16px;font-weight:900;color:#78350f;">Improvement Insights</div>
                <div style="font-size:12px;color:#a16207;">Auto-generated from your data — no manual input needed</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
            @foreach($insights as $ins)
                @php
                    $palette = match($ins['type']){
                        'good' => ['bg'=>'#dcfce7','border'=>'#86efac','iconBg'=>'#16a34a','color'=>'#166534'],
                        'warn' => ['bg'=>'#fee2e2','border'=>'#fca5a5','iconBg'=>'#dc2626','color'=>'#991b1b'],
                        default=> ['bg'=>'#dbeafe','border'=>'#93c5fd','iconBg'=>'#2563eb','color'=>'#1e3a8a'],
                    };
                @endphp
                <div style="background:{{ $palette['bg'] }};border:1px solid {{ $palette['border'] }};border-radius:12px;padding:14px;display:flex;gap:12px;align-items:flex-start;">
                    <div style="flex-shrink:0;width:32px;height:32px;border-radius:9px;background:{{ $palette['iconBg'] }};display:flex;align-items:center;justify-content:center;"><i class="fas {{ $ins['icon'] }}" style="color:#fff;font-size:13px;"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:800;color:{{ $palette['color'] }};font-size:13px;margin-bottom:3px;">{{ $ins['title'] }}</div>
                        <div style="font-size:12.5px;color:{{ $palette['color'] }};opacity:.9;line-height:1.45;">{{ $ins['msg'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

<script>
(function(){
    var months         = @json($months);
    var monthRevenue   = @json($monthRevenue);
    var monthOccupancy = @json($monthOccupancy);
    var monthBookings  = @json($monthBookings);
    var roomTypeLabels = @json($roomTypeLabels);
    var roomTypeData   = @json($roomTypeData);
    var sourceLabels   = @json($sourceLabels);
    var sourceCounts   = @json($sourceCounts);
    var dowLabels      = @json($dowLabels);
    var dowTotals      = @json($dowTotals);
    var pmLabels       = @json($pmLabels);
    var pmAmounts      = @json($pmAmounts);

    function fmtINR(v){
        v = Math.round(v||0);
        if (v >= 10000000) return '₹' + (v/10000000).toFixed(2) + 'Cr';
        if (v >= 100000)   return '₹' + (v/100000).toFixed(2) + 'L';
        if (v >= 1000)     return '₹' + (v/1000).toFixed(1) + 'K';
        return '₹' + v;
    }

    function ensureApex(cb){
        if (typeof ApexCharts !== 'undefined') return cb();
        var n=0, t=setInterval(function(){
            if (typeof ApexCharts !== 'undefined' || ++n > 40){ clearInterval(t); if (typeof ApexCharts !== 'undefined') cb(); }
        }, 100);
    }

    ensureApex(function(){
        // Monthly combo chart
        new ApexCharts(document.querySelector('#perfMonthlyChart'), {
            chart: { type:'line', height:340, toolbar:{ show:false }, fontFamily:'Inter, sans-serif' },
            series: [
                { name:'Revenue',   type:'column', data: monthRevenue },
                { name:'Occupancy %', type:'line', data: monthOccupancy },
            ],
            stroke: { width:[0,3], curve:'smooth' },
            colors: ['#10b981', '#0ea5e9'],
            plotOptions: { bar: { borderRadius:6, columnWidth:'55%' } },
            dataLabels: { enabled:false },
            xaxis: { categories: months, labels:{ style:{ fontSize:'11px', colors:'#64748b' } } },
            yaxis: [
                { seriesName:'Revenue', labels: { formatter: fmtINR, style:{ fontSize:'11px', colors:'#64748b' } }, title:{ text:'Revenue', style:{ fontSize:'11px', color:'#10b981', fontWeight:700 } } },
                { opposite:true, seriesName:'Occupancy %', max:100, labels:{ formatter: function(v){return Math.round(v)+'%';}, style:{ fontSize:'11px', colors:'#64748b' } }, title:{ text:'Occupancy %', style:{ fontSize:'11px', color:'#0ea5e9', fontWeight:700 } } },
            ],
            legend: { position:'top', horizontalAlign:'right', fontSize:'12px' },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            tooltip: { shared:true, intersect:false, y: [
                { formatter: function(v){ return '₹' + Number(v||0).toLocaleString('en-IN'); } },
                { formatter: function(v){ return v + '%'; } },
            ]},
        }).render();

        if (roomTypeData.length){
            new ApexCharts(document.querySelector('#perfRoomTypeChart'), {
                chart: { type:'donut', height:300, fontFamily:'Inter, sans-serif' },
                series: roomTypeData,
                labels: roomTypeLabels,
                colors: ['#f43f5e','#fb923c','#facc15','#84cc16','#06b6d4','#8b5cf6','#ec4899','#64748b'],
                legend: { position:'bottom', fontSize:'12px' },
                dataLabels: { enabled:true, formatter: function(v){ return Math.round(v) + '%'; } },
                plotOptions: { pie: { donut: { size:'62%', labels: { show:true, total: { show:true, label:'Bookings', formatter: function(w){ return w.globals.seriesTotals.reduce(function(a,b){return a+b;},0); } } } } } },
            }).render();
        }

        if (sourceCounts.length){
            new ApexCharts(document.querySelector('#perfSourceChart'), {
                chart: { type:'donut', height:300, fontFamily:'Inter, sans-serif' },
                series: sourceCounts,
                labels: sourceLabels,
                colors: ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#0ea5e9','#f43f5e','#64748b'],
                legend: { position:'bottom', fontSize:'12px' },
                dataLabels: { enabled:true, formatter: function(v){ return Math.round(v) + '%'; } },
                plotOptions: { pie: { donut: { size:'62%', labels: { show:true, total: { show:true, label:'Total', formatter: function(w){ return w.globals.seriesTotals.reduce(function(a,b){return a+b;},0); } } } } } },
            }).render();
        }

        new ApexCharts(document.querySelector('#perfDowChart'), {
            chart: { type:'bar', height:280, toolbar:{ show:false }, fontFamily:'Inter, sans-serif' },
            series: [{ name:'Revenue', data: dowTotals }],
            xaxis: { categories: dowLabels, labels:{ style:{ fontSize:'12px', colors:'#64748b', fontWeight:600 } } },
            yaxis: { labels: { formatter: fmtINR, style:{ fontSize:'11px', colors:'#64748b' } } },
            colors: ['#0ea5e9'],
            plotOptions: { bar: { borderRadius:8, columnWidth:'58%', distributed:false } },
            dataLabels: { enabled:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            tooltip: { y: { formatter: function(v){ return '₹' + Number(v||0).toLocaleString('en-IN'); } } },
            noData: { text:'No revenue in the last 90 days', style:{ color:'#94a3b8', fontSize:'13px' } },
        }).render();

        if (pmAmounts.length){
            new ApexCharts(document.querySelector('#perfPmChart'), {
                chart: { type:'bar', height:280, toolbar:{ show:false }, fontFamily:'Inter, sans-serif' },
                series: [{ name:'Revenue', data: pmAmounts }],
                xaxis: { categories: pmLabels, labels:{ style:{ fontSize:'12px', colors:'#64748b', fontWeight:700 } } },
                yaxis: { labels: { formatter: fmtINR, style:{ fontSize:'11px', colors:'#64748b' } } },
                colors: ['#22c55e'],
                plotOptions: { bar: { borderRadius:8, columnWidth:'48%', distributed:true } },
                dataLabels: { enabled:false },
                grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
                legend: { show:false },
                tooltip: { y: { formatter: function(v){ return '₹' + Number(v||0).toLocaleString('en-IN'); } } },
            }).render();
        }
    });
})();
</script>
@endsection
