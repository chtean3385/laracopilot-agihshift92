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
        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @method('PUT')
            @csrf

            {{-- Logo Upload Section --}}
            <div class="mb-6 pb-6 border-b border-gray-100">
                <h4 class="font-bold text-gray-700 mb-4"><i class="fas fa-image text-cyan-500 mr-2"></i>Resort Logo</h4>
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        @if($settings->logo && file_exists(public_path('storage/' . $settings->logo)))
                            <img src="{{ asset('storage/' . $settings->logo) }}" alt="Resort Logo"
                                 class="w-24 h-24 object-contain rounded-2xl border border-gray-200 bg-gray-50 p-2">
                        @else
                            <div class="w-24 h-24 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-umbrella-beach text-white text-3xl"></i>
                            </div>
                        @endif
                        @if($settings->logo)
                        <p class="text-xs text-center text-gray-400 mt-1">Current logo</p>
                        @else
                        <p class="text-xs text-center text-gray-400 mt-1">No logo set</p>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="form-label">Upload New Logo</label>
                        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.gif,.svg,.webp"
                               class="form-input" style="padding:8px;" id="logoInput">
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, SVG or WebP · Max 2 MB · Recommended: square or wide format</p>
                        <p class="text-xs text-gray-400 mt-0.5">Used in: sidebar, login screen, and invoice/billing headers</p>
                        <div id="logoPreview" class="mt-3 hidden">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Preview:</p>
                            <img id="previewImg" src="" alt="Preview" class="h-16 object-contain rounded-xl border border-gray-200 bg-gray-50 p-1">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="form-label">Resort Name <span class="text-red-500">*</span></label>
                    <input type="text" name="resort_name" value="{{ old('resort_name', $settings->resort_name) }}" class="form-input" required>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Tagline <span class="text-gray-400 font-normal text-xs">(shown below resort name in sidebar)</span></label>
                    <input type="text" name="tagline" value="{{ old('tagline', $settings->tagline ?? 'Resort & Spa CRM') }}"
                           class="form-input" placeholder="e.g. Resort & Spa CRM" maxlength="150">
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
        <script>
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('previewImg').src = ev.target.result;
                document.getElementById('logoPreview').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
        </script>
    </div>
</div>
@endsection
