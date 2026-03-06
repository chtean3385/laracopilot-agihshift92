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
        <form action="{{ route('customers.update', $customer->id) }}" method="POST" class="p-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-input @error('name') border-red-400 @enderror" required>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input @error('email') border-red-400 @enderror">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->format('Y-m-d')) }}" class="form-input">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">ID Type <span class="text-red-500">*</span></label>
                            <select name="id_type" class="form-input" required>
                                <option value="aadhaar" {{ old('id_type', $customer->id_type) == 'aadhaar' ? 'selected' : '' }}>Aadhaar Card</option>
                                <option value="passport" {{ old('id_type', $customer->id_type) == 'passport' ? 'selected' : '' }}>Passport</option>
                                <option value="driving_license" {{ old('id_type', $customer->id_type) == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                <option value="voter_id" {{ old('id_type', $customer->id_type) == 'voter_id' ? 'selected' : '' }}>Voter ID</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">ID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="id_number" value="{{ old('id_number', $customer->id_number) }}" class="form-input" required>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-input">{{ old('notes', $customer->notes) }}</textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('customers.show', $customer->id) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Update Guest</button>
            </div>
        </form>
    </div>
</div>
@endsection
