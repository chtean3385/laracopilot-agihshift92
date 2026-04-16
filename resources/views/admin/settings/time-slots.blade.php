@extends('layouts.admin')
@section('title', 'Time Slots & Add-Ons')
@section('page-title', 'Time Slots & Add-Ons')
@section('page-subtitle', 'Manage time-slot pricing blocks and room add-ons')

@section('content')
<div class="max-w-4xl space-y-6">

    {{-- ── Time Slots Card (only if time-slot-pricing module is active) ── --}}
    @if($showTimeSlots)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-800"><i class="fas fa-clock text-violet-500 mr-2"></i>Time Slots</h3>
                <p class="text-xs text-gray-400 mt-0.5">Define named time blocks guests can book (e.g. Day Use 9am–4pm)</p>
            </div>
            <button onclick="openSlotModal()" class="btn-primary text-sm">
                <i class="fas fa-plus mr-2"></i>Add Slot
            </button>
        </div>

        @if($slots->isEmpty())
        <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-full bg-violet-50 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-clock text-violet-300 text-2xl"></i>
            </div>
            <p class="text-gray-500 font-medium">No time slots yet</p>
            <p class="text-gray-400 text-sm mt-1">Add your first time slot to enable slot-based bookings</p>
            <button onclick="openSlotModal()" class="btn-primary text-sm mt-4">
                <i class="fas fa-plus mr-2"></i>Add First Slot
            </button>
        </div>
        @else
        <div class="divide-y divide-gray-50" id="slotList">
            @foreach($slots as $slot)
            <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors" id="slot-row-{{ $slot->id }}">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-gray-800">{{ $slot->name }}</span>
                        <span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5">
                            {{ $slot->start_time }} – {{ $slot->end_time }}{{ $slot->is_overnight ? ' (next day)' : '' }}
                        </span>
                        @if(!$slot->is_active)
                        <span class="text-xs bg-red-100 text-red-600 rounded-full px-2 py-0.5">Disabled</span>
                        @endif
                    </div>
                    @if($slot->description)
                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $slot->description }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <span class="font-bold text-violet-700">₹{{ number_format($slot->base_price) }}</span>
                    <span class="text-xs text-gray-400 block">base price</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleSlot({{ $slot->id }}, this)"
                        class="text-xs px-3 py-1.5 rounded-lg font-medium transition-colors {{ $slot->is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                        {{ $slot->is_active ? 'Active' : 'Inactive' }}
                    </button>
                    <button onclick="openSlotModal({{ json_encode($slot) }})" class="text-xs text-violet-600 hover:text-violet-800 px-2 py-1.5 rounded-lg hover:bg-violet-50 transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteSlot({{ $slot->id }}, '{{ addslashes($slot->name) }}')" class="text-xs text-red-400 hover:text-red-600 px-2 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif {{-- end $showTimeSlots --}}

    {{-- ── Add-Ons Card (always visible when either module is active) ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-800"><i class="fas fa-plus-circle text-amber-500 mr-2"></i>Room Add-Ons</h3>
                <p class="text-xs text-gray-400 mt-0.5">Chargeable extras (e.g. AC +₹200) that staff can add to any slot or hourly booking</p>
            </div>
            <button onclick="openAddOnModal()" class="btn-primary text-sm">
                <i class="fas fa-plus mr-2"></i>Add Add-On
            </button>
        </div>
        <div id="addOnList">
            @php $addOns = \App\Models\RoomAddOn::whereNull('room_id')->orderBy('name')->get(); @endphp
            @if($addOns->isEmpty())
            <div class="p-8 text-center text-gray-400 text-sm" id="addOnEmpty">
                <i class="fas fa-tag text-2xl mb-2 text-amber-200 block"></i>
                No add-ons yet. Add chargeable extras like "AC", "Heater", "Extra Towels".
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($addOns as $ao)
                <div class="flex items-center gap-4 px-6 py-3" id="addon-row-{{ $ao->id }}">
                    <div class="flex-1">
                        <span class="font-medium text-gray-800">{{ $ao->name }}</span>
                    </div>
                    <span class="font-bold text-amber-600">+₹{{ number_format($ao->price) }}</span>
                    <button onclick="deleteAddOn({{ $ao->id }}, '{{ addslashes($ao->name) }}')" class="text-red-400 hover:text-red-600 text-sm px-2 py-1 rounded hover:bg-red-50 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Slot Modal ─────────────────────────────────────────────── --}}
<div id="slotModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(0,0,0,.45)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50 rounded-t-2xl">
            <h3 id="slotModalTitle" class="font-bold text-gray-800"><i class="fas fa-clock text-violet-500 mr-2"></i>Add Time Slot</h3>
            <button onclick="closeSlotModal()" class="text-gray-400 hover:text-gray-600 w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="slotForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="slotId" value="">
            <div>
                <label class="form-label">Slot Name <span class="text-red-500">*</span></label>
                <input type="text" id="slotName" class="form-input" placeholder="e.g. Day Use, Night Stay, Morning" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Time <span class="text-red-500">*</span></label>
                    <input type="time" id="slotStart" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">End Time <span class="text-red-500">*</span></label>
                    <input type="time" id="slotEnd" class="form-input" required>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <input type="checkbox" id="slotOvernight" class="w-4 h-4 rounded text-violet-500">
                <label for="slotOvernight" class="text-sm text-gray-700 cursor-pointer">
                    Ends next day (overnight slot — e.g. 6pm–9am next morning)
                </label>
            </div>
            <div>
                <label class="form-label">Base Price (₹) <span class="text-red-500">*</span></label>
                <input type="number" id="slotPrice" class="form-input" placeholder="500" min="0" step="0.01" required>
            </div>
            <div>
                <label class="form-label">Description <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                <input type="text" id="slotDesc" class="form-input" placeholder="e.g. Includes pool access">
            </div>
            <div id="slotError" class="hidden bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm"></div>
            <div id="slotWarning" class="hidden bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm">
                <div class="flex items-start gap-2">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 flex-shrink-0"></i>
                    <div class="flex-1">
                        <div class="font-semibold text-amber-800 mb-1">Slot saved, but time range overlaps with:</div>
                        <ul id="slotWarningList" class="list-disc list-inside text-amber-700 space-y-0.5"></ul>
                        <p class="text-amber-600 text-xs mt-2">Overlapping slots are allowed (e.g. 24-hour packages), but guests cannot be double-booked for the same room. Staff will see conflicts when creating bookings.</p>
                        <button type="button" onclick="closeSlotModal(); window.location.reload();" class="mt-3 px-4 py-1.5 bg-amber-500 text-white rounded-lg text-xs font-semibold hover:bg-amber-600">OK, understood</button>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeSlotModal()" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" id="slotSubmitBtn" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save mr-2"></i>Save Slot
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Add-On Modal ───────────────────────────────────────────── --}}
<div id="addOnModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(0,0,0,.45)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50 rounded-t-2xl">
            <h3 class="font-bold text-gray-800"><i class="fas fa-plus-circle text-amber-500 mr-2"></i>Add Add-On</h3>
            <button onclick="closeAddOnModal()" class="text-gray-400 hover:text-gray-600 w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="addOnForm" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="form-label">Add-On Name <span class="text-red-500">*</span></label>
                <input type="text" id="aoName" class="form-input" placeholder="e.g. AC, Heater, Bonfire" required>
            </div>
            <div>
                <label class="form-label">Price (₹) <span class="text-red-500">*</span></label>
                <input type="number" id="aoPrice" class="form-input" placeholder="200" min="0" step="0.01" required>
            </div>
            <div id="aoError" class="hidden bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeAddOnModal()" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" id="aoSubmitBtn" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// ── Time Slot Modal ─────────────────────────────────────────────────────────
function openSlotModal(slot) {
    document.getElementById('slotError').classList.add('hidden');
    if (slot) {
        document.getElementById('slotModalTitle').innerHTML = '<i class="fas fa-edit text-violet-500 mr-2"></i>Edit Time Slot';
        document.getElementById('slotId').value    = slot.id;
        document.getElementById('slotName').value  = slot.name;
        document.getElementById('slotStart').value = slot.start_time;
        document.getElementById('slotEnd').value   = slot.end_time;
        document.getElementById('slotOvernight').checked = !!slot.is_overnight;
        document.getElementById('slotPrice').value = slot.base_price;
        document.getElementById('slotDesc').value  = slot.description || '';
    } else {
        document.getElementById('slotModalTitle').innerHTML = '<i class="fas fa-clock text-violet-500 mr-2"></i>Add Time Slot';
        document.getElementById('slotId').value    = '';
        document.getElementById('slotForm').reset();
    }
    document.getElementById('slotModal').classList.remove('hidden');
    document.getElementById('slotName').focus();
}
function closeSlotModal() { document.getElementById('slotModal').classList.add('hidden'); }

document.getElementById('slotForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id   = document.getElementById('slotId').value;
    const btn  = document.getElementById('slotSubmitBtn');
    const errEl = document.getElementById('slotError');
    errEl.classList.add('hidden');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    const url    = id ? '{{ url("/settings/time-slots") }}/' + id : '{{ route("time-slots.store") }}';
    const method = id ? 'PUT' : 'POST';
    const body   = {
        _token:       '{{ csrf_token() }}',
        name:         document.getElementById('slotName').value,
        start_time:   document.getElementById('slotStart').value,
        end_time:     document.getElementById('slotEnd').value,
        is_overnight: document.getElementById('slotOvernight').checked ? '1' : '0',
        base_price:   document.getElementById('slotPrice').value,
        description:  document.getElementById('slotDesc').value,
    };
    if (id) body._method = 'PUT';

    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (data.success) {
            if (data.warnings && data.warnings.length > 0) {
                // Show overlap warning inside modal before reloading
                const warnEl = document.getElementById('slotWarning');
                const warnList = document.getElementById('slotWarningList');
                warnList.innerHTML = data.warnings.map(w => `<li>${w}</li>`).join('');
                warnEl.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Saved — Review Warning Above';
            } else {
                closeSlotModal();
                window.location.reload();
            }
        }
        else { errEl.textContent = data.message || 'Failed to save.'; errEl.classList.remove('hidden'); }
    } catch (err) {
        errEl.textContent = 'Network error. Please try again.'; errEl.classList.remove('hidden');
    }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Slot';
});

async function toggleSlot(id, btn) {
    try {
        const res  = await fetch('{{ url("/settings/time-slots") }}/' + id + '/toggle', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = data.is_active ? 'Active' : 'Inactive';
            btn.className   = 'text-xs px-3 py-1.5 rounded-lg font-medium transition-colors ' + (data.is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200');
        }
    } catch(e) {}
}

async function deleteSlot(id, name) {
    if (!confirm('Delete slot "' + name + '"? This cannot be undone.')) return;
    try {
        const res = await fetch('{{ url("/settings/time-slots") }}/' + id, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ _method: 'DELETE' })
        });
        const data = await res.json();
        if (data.success) { document.getElementById('slot-row-' + id)?.remove(); }
    } catch(e) {}
}

// ── Add-On Modal ────────────────────────────────────────────────────────────
function openAddOnModal()  { document.getElementById('aoError').classList.add('hidden'); document.getElementById('addOnModal').classList.remove('hidden'); document.getElementById('aoName').focus(); }
function closeAddOnModal() { document.getElementById('addOnModal').classList.add('hidden'); document.getElementById('addOnForm').reset(); }

document.getElementById('addOnForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn   = document.getElementById('aoSubmitBtn');
    const errEl = document.getElementById('aoError');
    errEl.classList.add('hidden');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    try {
        const res  = await fetch('{{ route("add-ons.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name: document.getElementById('aoName').value, price: document.getElementById('aoPrice').value }),
        });
        const data = await res.json();
        if (data.success) { closeAddOnModal(); window.location.reload(); }
        else { errEl.textContent = data.message || 'Failed.'; errEl.classList.remove('hidden'); }
    } catch(err) {
        errEl.textContent = 'Network error.'; errEl.classList.remove('hidden');
    }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save';
});

async function deleteAddOn(id, name) {
    if (!confirm('Delete add-on "' + name + '"?')) return;
    try {
        const res = await fetch('{{ url("/settings/add-ons") }}/' + id, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ _method: 'DELETE' })
        });
        const data = await res.json();
        if (data.success) { document.getElementById('addon-row-' + id)?.remove(); }
    } catch(e) {}
}
</script>
@endpush
@endsection
