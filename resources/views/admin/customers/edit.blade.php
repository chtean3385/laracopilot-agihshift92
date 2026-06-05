@extends('layouts.admin')
@section('title', 'Edit Guest')
@section('page-title', 'Edit Guest Profile')
@section('page-subtitle', 'Update guest information')

@section('content')
<div class="max-w-4xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-edit text-amber-500 mr-2"></i>Edit: {{ $customer->name }}</h3>
        </div>
        <form action="{{ route('customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-input @error('name') border-red-400 @enderror" required>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input @error('phone') border-red-400 @enderror" placeholder="9876543210" required>
                    <p class="text-xs text-gray-400 mt-1">Indian numbers: 10 digits. Foreign guests: include country code (e.g. +447911123456).</p>
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input @error('email') border-red-400 @enderror">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Age <span class="text-gray-400 font-normal text-xs">(years)</span></label>
                    <input type="number" name="age" value="{{ old('age', $customer->age) }}" class="form-input @error('age') border-red-400 @enderror" placeholder="e.g. 35" min="1" max="120">
                    @error('age')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-input">{{ old('address', $customer->address) }}</textarea>
                </div>
                <div><label class="form-label">City</label><input type="text" name="city" value="{{ old('city', $customer->city) }}" class="form-input"></div>
                <div><label class="form-label">State</label><input type="text" name="state" value="{{ old('state', $customer->state) }}" class="form-input"></div>
                <div><label class="form-label">Country</label><input type="text" name="country" value="{{ old('country', $customer->country) }}" class="form-input"></div>
                <div><label class="form-label">Nationality</label><input type="text" name="nationality" value="{{ old('nationality', $customer->nationality) }}" class="form-input"></div>

                <div class="md:col-span-2 border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-700 mb-4"><i class="fas fa-id-card text-cyan-500 mr-2"></i>Identity Proof</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">ID / Document Type <span class="text-red-500">*</span></label>
                            <select name="id_type" id="editIdType" onchange="updateIdPlaceholder('editIdNumber', this.value)" class="form-input" required>
                                <option value="aadhaar"         {{ old('id_type', $customer->id_type) == 'aadhaar'         ? 'selected' : '' }}>Aadhaar Card</option>
                                <option value="passport"        {{ old('id_type', $customer->id_type) == 'passport'        ? 'selected' : '' }}>Passport</option>
                                <option value="driving_license" {{ old('id_type', $customer->id_type) == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                <option value="voter_id"        {{ old('id_type', $customer->id_type) == 'voter_id'        ? 'selected' : '' }}>Voter ID</option>
                                <option value="pan_card"        {{ old('id_type', $customer->id_type) == 'pan_card'        ? 'selected' : '' }}>PAN Card</option>
                                <option value="visa"            {{ old('id_type', $customer->id_type) == 'visa'            ? 'selected' : '' }}>Visa</option>
                                <option value="other"           {{ old('id_type', $customer->id_type) == 'other'           ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">ID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="id_number" id="editIdNumber"
                                value="{{ old('id_number', $customer->id_number) }}"
                                class="form-input @error('id_number') border-red-400 @enderror"
                                placeholder="Enter ID number"
                                style="text-transform:uppercase;"
                                oninput="this.value=this.value.toUpperCase()">
                            @error('id_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Upload More Documents <span class="text-gray-400 font-normal text-xs">(optional, multiple)</span></label>
                            <input type="file" name="documents[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="form-input" style="padding:8px;">
                            <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>JPG, PNG or PDF · Max 5 MB each · Adds to existing documents</p>
                            @if($customer->documents->count() > 0)
                            <p class="text-xs text-cyan-600 mt-1">
                                <i class="fas fa-paperclip mr-1"></i>{{ $customer->documents->count() }} document(s) already on file —
                                <a href="{{ route('documents.index', $customer->id) }}" class="underline">manage</a>
                            </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Travel Details --}}
                <div class="md:col-span-2 border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-700 mb-4"><i class="fas fa-plane text-cyan-500 mr-2"></i>Travel Details <span class="text-xs text-gray-400 font-normal">(for police report / guest register)</span></h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Arriving From (City)</label>
                            <input type="text" name="arrival_city" value="{{ old('arrival_city', $customer->arrival_city) }}" class="form-input" placeholder="e.g. Mumbai">
                        </div>
                        <div>
                            <label class="form-label">Purpose of Travel</label>
                            <select name="travel_reason" class="form-input">
                                <option value="">Select reason</option>
                                <option value="Leisure" {{ old('travel_reason', $customer->travel_reason) == 'Leisure' ? 'selected' : '' }}>Leisure / Vacation</option>
                                <option value="Business" {{ old('travel_reason', $customer->travel_reason) == 'Business' ? 'selected' : '' }}>Business</option>
                                <option value="Wedding" {{ old('travel_reason', $customer->travel_reason) == 'Wedding' ? 'selected' : '' }}>Wedding / Event</option>
                                <option value="Medical" {{ old('travel_reason', $customer->travel_reason) == 'Medical' ? 'selected' : '' }}>Medical</option>
                                <option value="Transit" {{ old('travel_reason', $customer->travel_reason) == 'Transit' ? 'selected' : '' }}>Transit</option>
                                <option value="Other" {{ old('travel_reason', $customer->travel_reason) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Departing To (City)</label>
                            <input type="text" name="dispatch_city" value="{{ old('dispatch_city', $customer->dispatch_city) }}" class="form-input" placeholder="e.g. Goa">
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-input">{{ old('notes', $customer->notes) }}</textarea>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-700 mb-1"><i class="fas fa-building text-violet-500 mr-2"></i>Company / Corporate Billing <span class="text-xs text-gray-400 font-normal">(optional — fill for B2B GST invoices)</span></h4>
                    <p class="text-xs text-gray-400 mb-4">If the guest is billing to a company, fill these to auto-populate the GST Tax Invoice.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" class="form-input" placeholder="Acme Pvt. Ltd.">
                        </div>
                        <div>
                            <label class="form-label">GSTIN <span class="text-gray-400 font-normal text-xs">(15 characters)</span></label>
                            <input type="text" name="gstin" value="{{ old('gstin', $customer->gstin) }}" class="form-input" placeholder="22AAAAA0000A1Z5" maxlength="15" style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('customers.show', $customer->id) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Update Guest</button>
            </div>
        </form>
    </div>
</div>
<script>
const idPlaceholders = {
    aadhaar:         'XXXX XXXX XXXX (12 digits)',
    passport:        'A1234567',
    driving_license: 'MH01 20110012345',
    voter_id:        'ABC1234567',
    pan_card:        'ABCDE1234F',
    visa:            'Visa number',
    other:           'ID number',
};
function updateIdPlaceholder(inputId, type) {
    const el = document.getElementById(inputId);
    if (el) el.placeholder = idPlaceholders[type] || 'Enter ID number';
}
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('editIdType');
    if (sel && sel.value) updateIdPlaceholder('editIdNumber', sel.value);
});
</script>
@endsection
