@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')
@section('page-subtitle', 'Manage staff roles and their access levels')

@section('content')

<div class="flex items-center justify-between mb-6">
    @canDo('roles.edit')
    @if(session('crm_user_role') === 'Super Admin')
    <a href="{{ route('roles.create') }}" class="btn-primary text-sm"><i class="fas fa-plus mr-2"></i>Create New Role</a>
    @endif
    @endCanDo
</div>

{{-- Role Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @foreach($roles as $role)
    @php
        $colors = ['Admin'=>['from-red-500','to-rose-600','#ef4444'],'Manager'=>['from-blue-500','to-indigo-600','#3b82f6'],'Receptionist'=>['from-green-500','to-emerald-600','#22c55e']];
        $c = $colors[$role->name] ?? ['from-slate-500','to-slate-700','#64748b'];
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r {{ $c[0] }} {{ $c[1] }} p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black">{{ $role->name }}</h3>
                    <p class="text-sm opacity-80 mt-0.5">{{ $role->description }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shield-halved text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-center">
                    <div class="text-2xl font-black text-gray-800">{{ $role->permissions_count }}</div>
                    <div class="text-xs text-gray-400">Permissions</div>
                </div>
                <div class="flex items-center gap-2">
                    @if($role->is_system)
                    <span class="badge-blue"><i class="fas fa-lock mr-1 text-xs"></i>System</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @canDo('roles.edit')
                <a href="{{ route('roles.edit', $role->id) }}" class="btn-primary text-sm flex-1 justify-center">
                    <i class="fas fa-edit mr-2"></i>Edit Permissions
                </a>
                @endCanDo
                @if(!$role->is_system && session('crm_user_role') === 'Super Admin')
                <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Delete this role?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger"><i class="fas fa-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Info box for Super Admin --}}
@if(session('crm_user_role') === 'Super Admin')
<div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-2xl p-6">
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-crown text-purple-600"></i>
        </div>
        <div>
            <h4 class="font-bold text-purple-900 mb-1">Super Admin Access</h4>
            <p class="text-purple-700 text-sm">You have full unrestricted access to the entire CRM regardless of role settings. Use the Edit Permissions buttons above to control what each staff role can access. Changes take effect the next time that user logs in.</p>
        </div>
    </div>
</div>
@endif

@endsection
