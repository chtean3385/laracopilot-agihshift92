@extends('layouts.admin')
@section('title','Settings')
@section('page-title','System Settings')
@section('page-subtitle','Configure your resort information')
@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-cog text-slate-500 mr-2"></i>Resort Configuration</h3>
        </div>
        <form action="{{ route('settings.update') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="form-label">Resort Name <span class="text-red-500">*</span></label>
                    <input type="text" name="resort_name" value="{{ old('resort_name', $settings->resort_name) }}" class="form-input" required>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Address <span class="text-red-500">*</span></label>
                    <textarea name="address" rows="2" class="form-input" required>{{ old('address', $settings->address) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Phone <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $settings->phone) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $settings->email) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Website</label>
                    <input type="text" name="website" value="{{ old('website', $settings->website) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">GST Number</label>
                    <input type="text" name="gst_number" value="{{ old('gst_number', $settings->gst_number) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Tax Rate (%) <span class="text-red-500">*</span></label>
                    <input type="number" name="tax_rate" value="{{ old('tax_rate', $settings->tax_rate) }}" step="0.01" min="0" max="100" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Currency Symbol <span class="text-red-500">*</span></label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings->currency_symbol) }}" class="form-input" maxlength="5" required>
                </div>
                <div>
                    <label class="form-label">Check-In Time <span class="text-red-500">*</span></label>
                    <input type="time" name="check_in_time" value="{{ old('check_in_time', $settings->check_in_time) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Check-Out Time <span class="text-red-500">*</span></label>
                    <input type="time" name="check_out_time" value="{{ old('check_out_time', $settings->check_out_time) }}" class="form-input" required>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Cancellation Policy</label>
                    <textarea name="cancellation_policy" rows="3" class="form-input">{{ old('cancellation_policy', $settings->cancellation_policy) }}</textarea>
                </div>
            </div>
            <div class="flex justify-end mt-6 pt-6 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
