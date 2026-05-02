@extends('layouts.admin')
@section('title', 'Add Guest')
@section('page-title', 'Add New Guest')
@section('page-subtitle', 'Create a new guest profile')

@section('content')
<div class="max-w-4xl">

    @if(session('warning_deleted_guest'))
    <div style="background:#fff7ed;border:1.5px solid #fdba74;border-radius:14px;padding:16px 20px;margin-bottom:18px;display:flex;gap:12px;align-items:flex-start;">
        <i class="fas fa-exclamation-triangle" style="color:#f97316;font-size:18px;flex-shrink:0;margin-top:2px;"></i>
        <div>
            <div style="font-weight:700;color:#9a3412;font-size:14px;margin-bottom:4px;">Previously Deleted Guest Detected</div>
            <div style="font-size:13px;color:#7c2d12;">{{ session('warning_deleted_guest')['message'] }}</div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-user-plus text-cyan-500 mr-2"></i>Guest Information</h3>
        </div>
        <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-400 @enderror" placeholder="Guest full name" required>
                    @error('name')<p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" id="phoneInput" value="{{ old('phone') }}" class="form-input @error('phone') border-red-400 @enderror" placeholder="9876543210" required>
                    <p class="text-xs text-gray-400 mt-1">Indian numbers: 10 digits (e.g. 9876543210). Foreign guests: include country code (e.g. +447911123456).</p>
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input @error('email') border-red-400 @enderror" placeholder="guest@email.com">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Age <span class="text-gray-400 font-normal text-xs">(years)</span></label>
                    <input type="number" name="age" value="{{ old('age') }}" class="form-input @error('age') border-red-400 @enderror" placeholder="e.g. 35" min="1" max="120">
                    @error('age')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-input" placeholder="Street address">{{ old('address') }}</textarea>
                </div>
                <div>
                    <label class="form-label">City</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="form-input" placeholder="City">
                </div>
                <div>
                    <label class="form-label">State</label>
                    <input type="text" name="state" value="{{ old('state') }}" class="form-input" placeholder="State">
                </div>
                <div>
                    <label class="form-label">Country</label>
                    <input type="text" name="country" value="{{ old('country', 'India') }}" class="form-input" placeholder="Country">
                </div>
                <div>
                    <label class="form-label">Nationality</label>
                    <input type="text" name="nationality" value="{{ old('nationality', 'Indian') }}" class="form-input" placeholder="Nationality">
                </div>

                <div class="md:col-span-2 border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-700 mb-4"><i class="fas fa-id-card text-cyan-500 mr-2"></i>Identity Proof</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">ID / Document Type <span class="text-red-500">*</span></label>
                            <select name="id_type" class="form-input @error('id_type') border-red-400 @enderror" required>
                                <option value="">Select Document Type</option>
                                <option value="aadhaar"         {{ old('id_type') == 'aadhaar'         ? 'selected' : '' }}>Aadhaar Card</option>
                                <option value="passport"        {{ old('id_type') == 'passport'        ? 'selected' : '' }}>Passport</option>
                                <option value="driving_license" {{ old('id_type') == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                <option value="voter_id"        {{ old('id_type') == 'voter_id'        ? 'selected' : '' }}>Voter ID</option>
                                <option value="pan_card"        {{ old('id_type') == 'pan_card'        ? 'selected' : '' }}>PAN Card</option>
                                <option value="visa"            {{ old('id_type') == 'visa'            ? 'selected' : '' }}>Visa</option>
                                <option value="other"           {{ old('id_type') == 'other'           ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('id_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Upload Documents <span class="text-gray-400 font-normal text-xs">(optional, multiple allowed)</span></label>
                            <input type="file" name="documents[]" multiple accept=".jpg,.jpeg,.png,.pdf"
                                class="form-input @error('documents.*') border-red-400 @enderror"
                                style="padding:8px;">
                            <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>JPG, PNG or PDF · Max 5 MB each · You can select multiple files</p>
                            @error('documents.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Notes / Special Requirements</label>
                    <textarea name="notes" rows="3" class="form-input" placeholder="Any special requirements or notes about this guest...">{{ old('notes') }}</textarea>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-700 mb-1"><i class="fas fa-building text-violet-500 mr-2"></i>Company / Corporate Billing <span class="text-xs text-gray-400 font-normal">(optional — fill for B2B GST invoices)</span></h4>
                    <p class="text-xs text-gray-400 mb-4">If the guest is billing to a company, fill these to auto-populate the GST Tax Invoice.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-input" placeholder="Acme Pvt. Ltd.">
                        </div>
                        <div>
                            <label class="form-label">GSTIN <span class="text-gray-400 font-normal text-xs">(15 characters)</span></label>
                            <input type="text" name="gstin" value="{{ old('gstin') }}" class="form-input" placeholder="22AAAAA0000A1Z5" maxlength="15" style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('customers.index') }}" class="btn-secondary"><i class="fas fa-times mr-2"></i>Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Save Guest Profile</button>
            </div>
        </form>
    </div>
</div>
@endsection
