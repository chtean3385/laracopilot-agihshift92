@extends('layouts.admin')

@section('title', $role ? 'Edit Role: ' . $role->name : 'Create Role')
@section('page-title', $role ? 'Edit: ' . $role->name : 'Create New Role')
@section('page-subtitle', 'Manage permissions for this role')

@section('content')

<form action="{{ $role ? route('roles.update', $role->id) : route('roles.store') }}" method="POST">
    @csrf
    @if($role) @method('PUT') @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Role Info --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-shield-halved text-cyan-500"></i> Role Details
                </h3>

                <div class="mb-4">
                    <label class="form-label">Role Name</label>
                    @if($role && $role->is_system)
                    <input type="text" value="{{ $role->name }}" class="form-input bg-gray-50 text-gray-400" disabled>
                    <p class="text-xs text-gray-400 mt-1"><i class="fas fa-lock mr-1"></i>System roles cannot be renamed</p>
                    @else
                    <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" class="form-input @error('name') border-red-400 @enderror" placeholder="e.g. Supervisor" required>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    @endif
                </div>

                <div class="mb-6">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Describe this role's responsibilities…">{{ old('description', $role->description ?? '') }}</textarea>
                </div>

                @if($role)
                <div class="bg-gray-50 rounded-xl p-4 mb-6 text-center">
                    <div class="text-3xl font-black text-gray-800">{{ $role->permissions_count ?? $role->permissions->count() }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">Permissions currently assigned</div>
                </div>
                @endif

                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn-secondary px-4">Cancel</a>
                </div>
            </div>
        </div>

        {{-- Permission Matrix --}}
        <div class="lg:col-span-2 space-y-4">
            @foreach($permissions as $module => $perms)
            @php
                $moduleIcons = [
                    'Guests'     => 'fas fa-users',
                    'Rooms'      => 'fas fa-door-open',
                    'Bookings'   => 'fas fa-calendar-check',
                    'Operations' => 'fas fa-exchange-alt',
                    'Payments'   => 'fas fa-credit-card',
                    'Invoices'   => 'fas fa-file-invoice-dollar',
                    'Reports'    => 'fas fa-chart-bar',
                    'Settings'   => 'fas fa-cog',
                    'System'     => 'fas fa-shield-halved',
                    'Users'      => 'fas fa-user-gear',
                    'WhatsApp'   => 'fab fa-whatsapp',
                    'Restaurant' => 'fas fa-utensils',
                    'Danger Zone'=> 'fas fa-triangle-exclamation',
                ];
                $isDangerZone = ($module === 'Danger Zone');
                $icon = $moduleIcons[$module] ?? 'fas fa-circle';
            @endphp
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden {{ $isDangerZone ? 'border-2 border-red-200' : 'border border-gray-100' }}">
                <div class="flex items-center justify-between px-6 py-4 {{ $isDangerZone ? 'border-b border-red-100 bg-red-50/50' : 'border-b border-gray-100' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $isDangerZone ? 'bg-red-100' : 'bg-cyan-50' }}">
                            <i class="{{ $icon }} text-sm {{ $isDangerZone ? 'text-red-500' : 'text-cyan-600' }}"></i>
                        </div>
                        <span class="font-bold {{ $isDangerZone ? 'text-red-700' : 'text-gray-800' }}">{{ $module }}</span>
                        @if($isDangerZone)
                        <span style="font-size:10px;background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;padding:1px 7px;border-radius:99px;font-weight:600;letter-spacing:.03em;">SaaS Admin only</span>
                        @endif
                    </div>
                    <button type="button" onclick="toggleModule('{{ Str::slug($module) }}')"
                        class="text-xs font-semibold {{ $isDangerZone ? 'text-red-500 hover:text-red-700' : 'text-cyan-600 hover:text-cyan-800' }}">Toggle All</button>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($perms as $perm)
                    @php
                        $isSaasOnly  = $perm->slug === 'data.truncate';
                        $isSuperAdmin = session('crm_user_role') === 'Super Admin';
                        $isLocked    = $isSaasOnly && !$isSuperAdmin;
                        $isChecked   = isset($rolePermissions) && in_array($perm->slug, $rolePermissions);
                    @endphp
                    @if($isLocked)
                    {{-- Locked for hotel admins: shown read-only, excluded from submission --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-red-100 bg-red-50/40 opacity-70"
                         title="Only Platform Admin can enable this permission">
                        <i class="fas fa-lock text-red-400 text-xs flex-shrink-0"></i>
                        <span class="text-sm font-medium text-red-400">{{ $perm->label }}</span>
                        <span class="ml-auto text-xs text-red-300 italic">SaaS Admin only</span>
                    </div>
                    @else
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-cyan-200 hover:bg-cyan-50/30 cursor-pointer transition-all group">
                        <input type="checkbox"
                            name="permissions[]"
                            value="{{ $perm->slug }}"
                            data-module="{{ Str::slug($module) }}"
                            class="w-4 h-4 rounded accent-cyan-500"
                            {{ $isChecked ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ $perm->label }}</span>
                    </label>
                    @endif
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
function toggleModule(module) {
    const boxes = document.querySelectorAll(`input[data-module="${module}"]`);
    const allChecked = [...boxes].every(b => b.checked);
    boxes.forEach(b => b.checked = !allChecked);
}
</script>
@endpush
