@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . session('crm_user_name') . '! Here\'s what\'s happening today.')

@section('content')
<div class="space-y-6">
    <!-- KPI Cards Row 1 -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Today's Check-Ins</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $todayCheckins->count() }}</p>
                    <p class="text-xs text-cyan-600 mt-1 font-medium">Pending arrival</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-sign-in-alt text-white"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Today's Check-Outs</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $todayCheckouts->count() }}</p>
                    <p class="text-xs text-amber-600 mt-1 font-medium">Pending departure</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-sign-out-alt text-white"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Available Rooms</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $availableRooms }}</p>
                    <p class="text-xs text-emerald-600 mt-1 font-medium">of {{ $totalRooms }} total</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-door-open text-white"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Occupied Rooms</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $occupiedRooms }}</p>
                    <p class="text-xs text-red-500 mt-1 font-medium">{{ $occupancyRate }}% occupancy</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-rose-400 to-red-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-bed text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards Row 2 -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Today's Revenue</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">₹{{ number_format($todayRevenue) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Collected today</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-violet-400 to-purple-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-rupee-sign text-white"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Month Revenue</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">₹{{ number_format($monthRevenue) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ now()->format('F Y') }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-chart-line text-white"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Pending Payments</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $pendingPayments }}</p>
                    <p class="text-xs text-amber-600 mt-1 font-medium">Needs attention</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-yellow-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-exclamation-triangle text-white"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Total Guests</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalCustomers }}</p>
                    <p class="text-xs text-blue-500 mt-1 font-medium">+{{ $newCustomersMonth }} this month</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-sky-400 to-blue-500 rounded-2xl flex items-center justify-center shadow-md">
                    <i class="fas fa-users text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Section: Room Status + Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Occupancy Chart -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-5">Room Occupancy</h3>
            <div class="flex items-center justify-center mb-4">
                <div class="relative w-36 h-36">
                    <svg viewBox="0 0 36 36" class="w-36 h-36 transform -rotate-90">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="url(#grad)" stroke-width="3" stroke-dasharray="{{ $occupancyRate }}, 100"/>
                        <defs>
                            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#06b6d4"/>
                                <stop offset="100%" style="stop-color:#3b82f6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-800">{{ $occupancyRate }}%</div>
                            <div class="text-xs text-gray-400">Occupied</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-emerald-400"></div><span class="text-sm text-gray-600">Available</span></div>
                    <span class="font-bold text-gray-700">{{ $availableRooms }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-red-400"></div><span class="text-sm text-gray-600">Occupied</span></div>
                    <span class="font-bold text-gray-700">{{ $occupiedRooms }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-amber-400"></div><span class="text-sm text-gray-600">Maintenance</span></div>
                    <span class="font-bold text-gray-700">{{ $maintenanceRooms }}</span>
                </div>
            </div>
        </div>

        <!-- Weekly Revenue Chart -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 col-span-1 lg:col-span-2">
            <h3 class="font-bold text-gray-800 mb-5">7-Day Revenue Overview</h3>
            @php $maxRevenue = max(array_column($weeklyRevenue, 'amount')) ?: 1; @endphp
            <div class="flex items-end gap-3 h-32">
                @foreach($weeklyRevenue as $day)
                    @php $height = $day['amount'] > 0 ? max(8, round(($day['amount'] / $maxRevenue) * 100)) : 4; @endphp
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="text-xs text-gray-500 font-medium">{{ $day['amount'] > 0 ? '₹' . number_format($day['amount']/1000, 0) . 'k' : '—' }}</div>
                        <div class="w-full bg-gradient-to-t from-cyan-500 to-blue-400 rounded-t-lg hover:from-cyan-600 hover:to-blue-500 transition-all cursor-pointer" style="height: {{ $height }}%;"></div>
                        <div class="text-xs text-gray-400">{{ $day['day'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Bottom Section: Today's Activity + Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-5">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('bookings.create') }}" class="flex items-center gap-3 p-3 bg-gradient-to-r from-cyan-50 to-blue-50 hover:from-cyan-100 hover:to-blue-100 rounded-xl transition-all group">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-plus text-white text-sm"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-700 text-sm group-hover:text-blue-700">New Booking</div>
                        <div class="text-xs text-gray-400">Create reservation</div>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-gray-300 text-xs"></i>
                </a>
                <a href="{{ route('checkin.index') }}" class="flex items-center gap-3 p-3 bg-gradient-to-r from-emerald-50 to-teal-50 hover:from-emerald-100 hover:to-teal-100 rounded-xl transition-all group">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-sign-in-alt text-white text-sm"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-700 text-sm group-hover:text-emerald-700">Process Check-In</div>
                        <div class="text-xs text-gray-400">{{ $todayCheckins->count() }} pending</div>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-gray-300 text-xs"></i>
                </a>
                <a href="{{ route('checkout.index') }}" class="flex items-center gap-3 p-3 bg-gradient-to-r from-amber-50 to-orange-50 hover:from-amber-100 hover:to-orange-100 rounded-xl transition-all group">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-sign-out-alt text-white text-sm"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-700 text-sm group-hover:text-amber-700">Process Check-Out</div>
                        <div class="text-xs text-gray-400">{{ $todayCheckouts->count() }} pending</div>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-gray-300 text-xs"></i>
                </a>
                <a href="{{ route('customers.create') }}" class="flex items-center gap-3 p-3 bg-gradient-to-r from-violet-50 to-purple-50 hover:from-violet-100 hover:to-purple-100 rounded-xl transition-all group">
                    <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-500 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-user-plus text-white text-sm"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-700 text-sm group-hover:text-violet-700">Add Guest</div>
                        <div class="text-xs text-gray-400">New guest profile</div>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-gray-300 text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 col-span-1 lg:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-gray-800">Recent Bookings</h3>
                <a href="{{ route('bookings.index') }}" class="text-cyan-600 hover:text-cyan-700 text-sm font-medium">View All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="space-y-3">
                @forelse($recentBookings as $booking)
                <div class="flex items-center gap-4 p-3 hover:bg-gray-50 rounded-xl transition-all">
                    <div class="w-10 h-10 bg-gradient-to-br from-slate-100 to-slate-200 rounded-full flex items-center justify-center text-slate-600 font-bold text-sm flex-shrink-0">
                        {{ substr($booking->customer->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-800 text-sm truncate">{{ $booking->customer->name }}</div>
                        <div class="text-xs text-gray-400">Room {{ $booking->room->room_number }} • {{ $booking->check_in_date->format('d M') }} - {{ $booking->check_out_date->format('d M') }}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-bold text-gray-700">₹{{ number_format($booking->total_amount) }}</div>
                        <span class="badge-{{ $booking->status_color }} text-xs">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-calendar-times text-3xl mb-2"></i>
                    <p class="text-sm">No recent bookings</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Today's Check-In & Check-Out Lists -->
    @if($todayCheckins->count() > 0 || $todayCheckouts->count() > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if($todayCheckins->count() > 0)
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-in-alt text-white text-xs"></i>
                </div>
                <h3 class="font-bold text-gray-800">Today's Arrivals ({{ $todayCheckins->count() }})</h3>
            </div>
            <div class="space-y-3">
                @foreach($todayCheckins as $booking)
                <div class="flex items-center justify-between p-3 bg-cyan-50 rounded-xl">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">{{ $booking->customer->name }}</div>
                        <div class="text-xs text-gray-500">Room {{ $booking->room->room_number }} • {{ $booking->nights }} night(s)</div>
                    </div>
                    <a href="{{ route('checkin.show', $booking->id) }}" class="bg-cyan-500 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-cyan-600 transition-all font-medium">
                        Check In
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($todayCheckouts->count() > 0)
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-out-alt text-white text-xs"></i>
                </div>
                <h3 class="font-bold text-gray-800">Today's Departures ({{ $todayCheckouts->count() }})</h3>
            </div>
            <div class="space-y-3">
                @foreach($todayCheckouts as $booking)
                <div class="flex items-center justify-between p-3 bg-amber-50 rounded-xl">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">{{ $booking->customer->name }}</div>
                        <div class="text-xs text-gray-500">Room {{ $booking->room->room_number }} • Due: ₹{{ number_format($booking->balance_due) }}</div>
                    </div>
                    <a href="{{ route('checkout.show', $booking->id) }}" class="bg-amber-500 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-amber-600 transition-all font-medium">
                        Check Out
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
