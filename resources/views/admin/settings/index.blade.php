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
        {{-- GST misconfiguration warning banner --}}
        @if(($settings->invoice_style ?? 'modern') === 'gst' && (empty($settings->gst_number) || (float)($settings->tax_rate ?? 0) == 0))
        <div class="mx-6 mt-6 p-4 bg-amber-50 border-2 border-amber-400 rounded-xl flex gap-3">
            <i class="fas fa-exclamation-triangle text-amber-500 text-lg mt-0.5 flex-shrink-0"></i>
            <div class="text-sm text-amber-800">
                <p class="font-bold text-amber-900 mb-1">GST Invoice is ON — but required fields are missing!</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @if(empty($settings->gst_number))
                        <li><strong>GST Number</strong> is empty — invoices will print without a GSTIN.</li>
                    @endif
                    @if((float)($settings->tax_rate ?? 0) == 0)
                        <li><strong>GST Rate (Tax Rate)</strong> is 0% — no GST will be added to any billing.</li>
                    @endif
                </ul>
                <p class="mt-2 text-amber-700">Fill in both fields below and save, otherwise GST will never be charged or shown on invoices.</p>
            </div>
        </div>
        @endif

        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @method('PUT')
            @csrf

            {{-- Logo Upload Section --}}
            <div class="mb-6 pb-6 border-b border-gray-100">
                <h4 class="font-bold text-gray-700 mb-4"><i class="fas fa-image text-cyan-500 mr-2"></i>Resort Logo</h4>
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        @if($settings->logo_url)
                            <img src="{{ $settings->logo_url }}" alt="Resort Logo"
                                 class="w-24 h-24 object-contain rounded-2xl border border-gray-200 bg-gray-50 p-2">
                        @else
                            <div class="w-24 h-24 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-umbrella-beach text-white text-3xl"></i>
                            </div>
                        @endif
                        @if($settings->logo_url)
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
                    <label class="form-label">Room Tax / GST Rate (%) <span class="text-red-500">*</span></label>
                    <input type="number" name="tax_rate" value="{{ old('tax_rate', $settings->tax_rate) }}" step="0.01" min="0" max="100" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Restaurant / Food GST Rate (%)</label>
                    <input type="number" name="food_tax_rate" value="{{ old('food_tax_rate', $settings->food_tax_rate ?? 5) }}" step="0.01" min="0" max="100" class="form-input" placeholder="5">
                    <p class="text-xs text-gray-400 mt-1">Applied separately on food &amp; extra service charges in invoices. Default: 5%</p>
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
            {{-- Invoice Print Style --}}
            <div class="mt-8 pt-6 border-t border-gray-100">
                <h4 class="font-bold text-gray-700 mb-4"><i class="fas fa-file-invoice text-violet-500 mr-2"></i>Invoice Print Style</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all {{ old('invoice_style', $settings->invoice_style ?? 'modern') === 'modern' ? 'border-violet-400 bg-violet-50' : 'border-gray-200 hover:border-violet-200' }}" onclick="setStyle('modern')">
                        <input type="radio" name="invoice_style" value="modern" class="mt-1" {{ old('invoice_style', $settings->invoice_style ?? 'modern') === 'modern' ? 'checked' : '' }}>
                        <div>
                            <div class="font-bold text-gray-700 text-sm">Modern</div>
                            <div class="text-xs text-gray-400 mt-0.5">Clean card layout with dark header. Works for all hotels.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all {{ old('invoice_style', $settings->invoice_style ?? 'modern') === 'gst' ? 'border-violet-400 bg-violet-50' : 'border-gray-200 hover:border-violet-200' }}" onclick="setStyle('gst')">
                        <input type="radio" name="invoice_style" value="gst" class="mt-1" {{ old('invoice_style', $settings->invoice_style ?? 'modern') === 'gst' ? 'checked' : '' }}>
                        <div>
                            <div class="font-bold text-gray-700 text-sm">GST Tax Invoice</div>
                            <div class="text-xs text-gray-400 mt-0.5">Formal Indian GST format with CGST/SGST split, HSN codes, bank details &amp; advance summary.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all {{ old('invoice_style', $settings->invoice_style ?? 'modern') === 'compact' ? 'border-violet-400 bg-violet-50' : 'border-gray-200 hover:border-violet-200' }}" onclick="setStyle('compact')">
                        <input type="radio" name="invoice_style" value="compact" class="mt-1" {{ old('invoice_style', $settings->invoice_style ?? 'modern') === 'compact' ? 'checked' : '' }}>
                        <div>
                            <div class="font-bold text-gray-700 text-sm">Compact (Classic)</div>
                            <div class="text-xs text-gray-400 mt-0.5">Old-style dense format — all details in bordered tables, no colors. Ideal for dot-matrix or narrow printers.</div>
                        </div>
                    </label>
                </div>
                {{-- Live warning shown when GST style is selected but fields are not filled --}}
                <div id="gst-inline-warn" class="hidden mt-3 p-3 bg-red-50 border border-red-300 rounded-xl text-sm text-red-800 flex gap-2">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <strong>Action required before saving:</strong>
                        <ul id="gst-warn-list" class="list-disc list-inside mt-1 space-y-0.5"></ul>
                        <p class="mt-1 text-red-700 text-xs">Scroll down to the GST fields and fill them in before saving, or invoices will have no GST applied.</p>
                    </div>
                </div>
            </div>

            {{-- Bank & Invoice Details --}}
            <div class="mt-8 pt-6 border-t border-gray-100">
                <h4 class="font-bold text-gray-700 mb-1"><i class="fas fa-university text-emerald-500 mr-2"></i>Bank &amp; GST Invoice Details</h4>
                <p class="text-xs text-gray-400 mb-4">Required for GST Tax Invoice format. Also printed on bank transfer receipts.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Second Contact Number</label>
                        <input type="text" name="contact_number" value="{{ old('contact_number', $settings->contact_number ?? '') }}" class="form-input" placeholder="e.g. +91 98765 43210">
                    </div>
                    <div>
                        <label class="form-label">State / Code <span class="text-gray-400 font-normal text-xs">(e.g. GUJARAT/24)</span></label>
                        <input type="text" name="state_code" value="{{ old('state_code', $settings->state_code ?? '') }}" class="form-input" placeholder="e.g. GUJARAT/24">
                    </div>
                    <div>
                        <label class="form-label">HSN/SAC Code — Room <span class="text-gray-400 font-normal text-xs">(default 996311)</span></label>
                        <input type="text" name="hsn_room" value="{{ old('hsn_room', $settings->hsn_room ?? '996311') }}" class="form-input" placeholder="996311">
                    </div>
                    <div>
                        <label class="form-label">HSN/SAC Code — Food <span class="text-gray-400 font-normal text-xs">(default 996331)</span></label>
                        <input type="text" name="hsn_food" value="{{ old('hsn_food', $settings->hsn_food ?? '996331') }}" class="form-input" placeholder="996331">
                    </div>
                    <div>
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $settings->bank_name ?? '') }}" class="form-input" placeholder="e.g. State Bank of India">
                    </div>
                    <div>
                        <label class="form-label">Bank Account Number</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $settings->bank_account_number ?? '') }}" class="form-input" placeholder="e.g. 1234567890">
                    </div>
                    <div>
                        <label class="form-label">IFSC Code</label>
                        <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $settings->bank_ifsc ?? '') }}" class="form-input" placeholder="e.g. SBIN0001234">
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6 pt-6 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Save Settings</button>
            </div>
        </form>
        <script>
        function checkGstWarn() {
            var gstNum   = document.querySelector('input[name="gst_number"]');
            var taxRate  = document.querySelector('input[name="tax_rate"]');
            var warn     = document.getElementById('gst-inline-warn');
            var list     = document.getElementById('gst-warn-list');
            var isGst    = document.querySelector('input[name="invoice_style"][value="gst"]')?.checked;
            if (!warn || !isGst) { warn && warn.classList.add('hidden'); return; }
            var issues = [];
            if (!gstNum || !gstNum.value.trim()) issues.push('GST Number is empty');
            if (!taxRate || parseFloat(taxRate.value || 0) === 0) issues.push('Tax Rate is 0%');
            if (issues.length > 0) {
                list.innerHTML = issues.map(function(i){ return '<li>' + i + '</li>'; }).join('');
                warn.classList.remove('hidden');
            } else {
                warn.classList.add('hidden');
            }
        }
        function setStyle(val) {
            document.querySelectorAll('input[name="invoice_style"]').forEach(function(r) {
                var lbl = r.closest('label');
                if (r.value === val) {
                    r.checked = true;
                    lbl.classList.add('border-violet-400','bg-violet-50');
                    lbl.classList.remove('border-gray-200');
                } else {
                    lbl.classList.remove('border-violet-400','bg-violet-50');
                    lbl.classList.add('border-gray-200');
                }
            });
            checkGstWarn();
        }
        document.addEventListener('DOMContentLoaded', function() {
            checkGstWarn();
            var gstNum  = document.querySelector('input[name="gst_number"]');
            var taxRate = document.querySelector('input[name="tax_rate"]');
            if (gstNum)  gstNum.addEventListener('input', checkGstWarn);
            if (taxRate) taxRate.addEventListener('input', checkGstWarn);
        });
        </script>
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
