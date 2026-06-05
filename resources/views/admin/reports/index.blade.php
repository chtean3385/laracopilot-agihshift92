@extends('layouts.admin')
@section('title','Reports')
@section('page-title','Reports & Analytics')
@section('page-subtitle','Detailed business insights')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <a href="{{ route('reports.performance') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group" style="position:relative;overflow:hidden;">
        <div style="position:absolute;top:14px;right:14px;background:linear-gradient(135deg,#fb7185,#f43f5e);color:#fff;font-size:10px;font-weight:800;padding:3px 9px;border-radius:999px;letter-spacing:.05em;">NEW</div>
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
            <i class="fas fa-chart-pie text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#c9a96e] transition-colors">Performance Analysis</h3>
        <p class="text-gray-400 text-sm mt-1">Monthly trends · ADR · RevPAR · channel mix · improvement insights</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Insights <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    <a href="{{ route('reports.revenue') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#c9a96e,#b08d56);">
            <i class="fas fa-chart-line text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#c9a96e] transition-colors">Revenue Report</h3>
        <p class="text-gray-400 text-sm mt-1">Daily, monthly, and payment method breakdown</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    <a href="{{ route('reports.occupancy') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#7a8a9a,#5a6a7a);">
            <i class="fas fa-bed text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#7a8a9a] transition-colors">Occupancy Report</h3>
        <p class="text-gray-400 text-sm mt-1">Room occupancy rates and availability analysis</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    <a href="{{ route('reports.bookings') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#7a8a9a,#5a6a7a);">
            <i class="fas fa-calendar-check text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#7a8a9a] transition-colors">Booking Report</h3>
        <p class="text-gray-400 text-sm mt-1">All bookings with status and guest details</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    <a href="{{ route('reports.guest_register') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#c9a96e,#b08d56);">
            <i class="fas fa-id-card text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#c9a96e] transition-colors">Guest Register</h3>
        <p class="text-gray-400 text-sm mt-1">Police register — all guests, IDs, signatures by date</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Register <i class="fas fa-arrow-right ml-1"></i></div>
    </a>

    @if(\App\Models\Module::isEnabled('restaurant') && \App\Services\PermissionService::check('restaurant.reports'))
    <a href="{{ route('restaurant.reports') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#c9a96e,#b08d56);">
            <i class="fas fa-utensils text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#c9a96e] transition-colors">Restaurant Sales</h3>
        <p class="text-gray-400 text-sm mt-1">Bills, food revenue & payment breakdown</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    @endif

    @if(\App\Models\Module::isEnabled('inventory'))
    <a href="{{ route('reports.inventory_stock') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#7a8a9a,#5a6a7a);">
            <i class="fas fa-boxes text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#7a8a9a] transition-colors">Inventory Stock Report</h3>
        <p class="text-gray-400 text-sm mt-1">Current stock, low-stock alerts & total value</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    <a href="{{ route('reports.inventory_movements') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#c9a96e,#b08d56);">
            <i class="fas fa-exchange-alt text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#c9a96e] transition-colors">Inventory Movements</h3>
        <p class="text-gray-400 text-sm mt-1">Stock-in, usage & adjustments by date</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    @endif

    @if(\App\Models\Module::isEnabled('time-slot-pricing'))
    <a href="{{ route('reports.slot_availability') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#c9a96e,#b08d56);">
            <i class="fas fa-clock text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#c9a96e] transition-colors">Slot Availability</h3>
        <p class="text-gray-400 text-sm mt-1">Time-slot occupancy by date — available vs booked</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    <a href="{{ route('reports.slot_bookings') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 card-hover group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md" style="background:linear-gradient(135deg,#c9a96e,#b08d56);">
            <i class="fas fa-receipt text-white text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#c9a96e] transition-colors">Slot Bookings Report</h3>
        <p class="text-gray-400 text-sm mt-1">Revenue, per-slot breakdown & full booking list</p>
        <div class="mt-4 text-[#c9a96e] text-sm font-semibold">View Report <i class="fas fa-arrow-right ml-1"></i></div>
    </a>
    @endif
</div>
@endsection
