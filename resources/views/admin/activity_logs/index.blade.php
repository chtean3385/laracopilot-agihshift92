@extends('layouts.admin')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')
@section('page-subtitle', 'Track every action performed in the CRM')

@section('content')

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" action="{{ route('activity_log.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-44">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="User name, email, description…">
        </div>
        <div class="w-44">
            <label class="form-label">Module</label>
            <select name="module" class="form-input">
                <option value="">All Modules</option>
                @foreach($modules as $mod)
                <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <label class="form-label">Action</label>
            <select name="action" class="form-input">
                <option value="">All Actions</option>
                @foreach($actions as $act)
                <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <label class="form-label">Date</label>
            <input type="date" name="date" value="{{ request('date') }}" class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary text-sm px-4 py-2.5"><i class="fas fa-search mr-1"></i>Filter</button>
            <a href="{{ route('activity_log.index') }}" class="btn-secondary text-sm px-4 py-2.5">Clear</a>
        </div>
    </form>
</div>

{{-- Log Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h2 class="text-base font-bold text-gray-800">Audit Trail</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $logs->total() }} entries found</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <i class="fas fa-shield-halved text-cyan-500"></i>
            Real-time activity tracking
        </div>
    </div>

    @if($logs->isEmpty())
    <div class="py-20 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-history text-gray-300 text-2xl"></i>
        </div>
        <p class="text-gray-400 font-medium">No activity logs found</p>
        <p class="text-gray-300 text-sm mt-1">Logs will appear here as actions are performed</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left text-xs font-700 text-gray-500 uppercase tracking-wide px-6 py-3">Date / Time</th>
                    <th class="text-left text-xs font-700 text-gray-500 uppercase tracking-wide px-4 py-3">User</th>
                    <th class="text-left text-xs font-700 text-gray-500 uppercase tracking-wide px-4 py-3">Role</th>
                    <th class="text-left text-xs font-700 text-gray-500 uppercase tracking-wide px-4 py-3">Module</th>
                    <th class="text-left text-xs font-700 text-gray-500 uppercase tracking-wide px-4 py-3">Action</th>
                    <th class="text-left text-xs font-700 text-gray-500 uppercase tracking-wide px-4 py-3">Description</th>
                    <th class="text-left text-xs font-700 text-gray-500 uppercase tracking-wide px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($logs as $log)
                @php
                    $roleBadge = match($log->user_role) {
                        'Super Admin' => 'badge-purple',
                        'Admin'       => 'badge-red',
                        'Manager'     => 'badge-blue',
                        'Receptionist'=> 'badge-green',
                        default       => 'badge-gray',
                    };
                    $actionBadge = match(true) {
                        str_contains($log->action, 'Delete') || $log->action === 'Deleted' => 'badge-red',
                        str_contains($log->action, 'Create') || $log->action === 'Created' => 'badge-green',
                        str_contains($log->action, 'Update') || $log->action === 'Updated' => 'badge-blue',
                        str_contains($log->action, 'Checked') => 'badge-purple',
                        default => 'badge-gray',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-3">
                        <div class="text-sm font-semibold text-gray-800">{{ $log->created_at->format('d M Y') }}</div>
                        <div class="text-xs text-gray-400">{{ $log->created_at->format('h:i:s A') }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-semibold text-gray-800">{{ $log->user_name }}</div>
                        <div class="text-xs text-gray-400">{{ $log->user_email }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="{{ $roleBadge }}">{{ $log->user_role }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge-gray">{{ $log->module }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="{{ $actionBadge }}">{{ $log->action }}</span>
                    </td>
                    <td class="px-4 py-3 max-w-xs">
                        <span class="text-sm text-gray-600">{{ $log->description }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $logs->links() }}
    </div>
    @endif
    @endif
</div>

@endsection
