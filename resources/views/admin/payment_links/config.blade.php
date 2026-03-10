@extends('layouts.admin')
@section('title','Payment Links Config')
@section('page-title','Payment Links')
@section('page-subtitle','Configure UPI QR and Razorpay for invoices')
@section('content')
<div class="max-w-3xl space-y-6">
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
        <i class="fas fa-check-circle"></i>{{ session('success') }}
    </div>
    @endif

    <form action="{{ route('payment_links.config.save') }}" method="POST" class="space-y-6">
        @csrf

        {{-- UPI Section --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-qrcode text-violet-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">UPI QR Code</h3>
                        <p class="text-xs text-gray-500">Guests scan and pay via GPay, PhonePe, Paytm — no API needed</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="upi_enabled" value="1" class="sr-only peer" {{ $config->upi_enabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-500"></div>
                    <span class="ml-2 text-sm font-medium text-gray-600">Enable</span>
                </label>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Your UPI ID <span class="text-red-500">*</span></label>
                    <input type="text" name="upi_id" value="{{ old('upi_id', $config->upi_id) }}"
                           class="form-input" placeholder="resort@okaxis or 9876543210@ybl">
                    <p class="text-xs text-gray-400 mt-1">Found in your GPay / PhonePe / bank app settings</p>
                </div>
                <div>
                    <label class="form-label">Display Name</label>
                    <input type="text" name="upi_name" value="{{ old('upi_name', $config->upi_name) }}"
                           class="form-input" placeholder="Azure Paradise Resort">
                    <p class="text-xs text-gray-400 mt-1">Shown to guest on UPI payment screen</p>
                </div>
                <div class="md:col-span-2 bg-violet-50 border border-violet-100 rounded-xl p-4 text-sm text-violet-700">
                    <p class="font-semibold mb-1"><i class="fas fa-info-circle mr-1"></i>How it works</p>
                    <p>On any invoice with a balance due, a <strong>UPI QR</strong> button appears. Click it to show a QR code — the guest scans it and pays instantly. The amount is pre-filled. No account sign-up needed.</p>
                </div>
            </div>
        </div>

        {{-- Razorpay Section --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-cyan-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-link text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Razorpay Payment Links</h3>
                        <p class="text-xs text-gray-500">Send a link guests pay online via card, UPI, netbanking or wallet</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="razorpay_enabled" value="1" class="sr-only peer" {{ $config->razorpay_enabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                    <span class="ml-2 text-sm font-medium text-gray-600">Enable</span>
                </label>
            </div>
            <div class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Razorpay Key ID <span class="text-red-500">*</span></label>
                        <input type="text" name="razorpay_key_id" value="{{ old('razorpay_key_id', $config->razorpay_key_id) }}"
                               class="form-input font-mono text-sm" placeholder="rzp_live_xxxxxxxxxxxxxxxxx">
                    </div>
                    <div>
                        <label class="form-label">Razorpay Key Secret <span class="text-red-500">*</span></label>
                        <input type="password" name="razorpay_key_secret" value="{{ old('razorpay_key_secret', $config->razorpay_key_secret) }}"
                               class="form-input font-mono text-sm" placeholder="••••••••••••••••••••">
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 space-y-2 text-sm text-blue-700">
                    <p class="font-semibold"><i class="fas fa-info-circle mr-1"></i>Setup Steps</p>
                    <ol class="list-decimal ml-4 space-y-1">
                        <li>Sign up at <a href="https://razorpay.com" target="_blank" class="underline font-medium">razorpay.com</a> (free account)</li>
                        <li>Go to <strong>Settings → API Keys → Generate Key</strong></li>
                        <li>Copy your <strong>Key ID</strong> and <strong>Key Secret</strong> above</li>
                        <li>For auto-payment recording, set webhook URL in Razorpay dashboard to:<br>
                            <code class="bg-white border border-blue-200 rounded px-2 py-0.5 text-xs font-mono select-all">{{ route('payment_links.razorpay.webhook') }}</code>
                        </li>
                    </ol>
                </div>

                <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-sm text-amber-700">
                    <p class="font-semibold"><i class="fas fa-bolt mr-1"></i>How it works</p>
                    <p>On any invoice, click <strong>Send Razorpay Link</strong> — a unique payment link is generated for the exact balance due. Copy and share it via WhatsApp or email. When the guest pays, the invoice auto-updates to paid.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary px-8"><i class="fas fa-save mr-2"></i>Save Configuration</button>
        </div>
    </form>
</div>
@endsection
