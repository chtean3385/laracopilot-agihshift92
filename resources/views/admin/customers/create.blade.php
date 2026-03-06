@extends('layouts.admin')
@section('title', 'Add Guest')
@section('page-title', 'Add New Guest')
@section('page-subtitle', 'Create a new guest profile')

@section('content')
<div class="max-w-4xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-user-plus text-cyan-500 mr-2"></i>Guest Information</h3>
        </div>
        <form action="{{ route('customers.store') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-400 @enderror" placeholder="Guest full name" required>
                    @error('name')<p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-input @error('phone') border-red-400 @enderror" placeholder="+91 XXXXX XXXXX" required>
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input @error('email') border-red-400 @enderror" placeholder="guest@email.com">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-input">
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
                            <label class="form-label">ID Type <span class="text-red-500">*</span></label>
                            <select name="id_type" class="form-input @error('id_type') border-red-400 @enderror" required>
                                <option value="">Select ID Type</option>
                                <option value="aadhaar" {{ old('id_type') == 'aadhaar' ? 'selected' : '' }}>Aadhaar Card</option>
                                <option value="passport" {{ old('id_type') == 'passport' ? 'selected' : '' }}>Passport</option>
                                <option value="driving_license" {{ old('id_type') == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                <option value="voter_id" {{ old('id_type') == 'voter_id' ? 'selected' : '' }}>Voter ID</option>
                            </select>
                            @error('id_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">ID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="id_number" value="{{ old('id_number') }}" class="form-input @error('id_number') border-red-400 @enderror" placeholder="ID number" required>
                            @error('id_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Notes / Special Requirements</label>
                    <textarea name="notes" rows="3" class="form-input" placeholder="Any special requirements or notes about this guest...">{{ old('notes') }}</textarea>
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
