@extends('layouts.admin')
@section('title', 'Booking Widget Settings')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Booking Widget</h1>
            <p class="text-sm text-gray-500 mt-0.5">Embed a booking form on your website so guests can book directly.</p>
        </div>
        <a href="{{ route('modules.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
            ← Back to Modules
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm font-medium">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Settings Form --}}
        <div class="lg:col-span-2 space-y-5">
            <form method="POST" action="{{ route('admin.booking-widget.settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Appearance --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h2 class="font-semibold text-gray-800 mb-4">Appearance</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Widget Title</label>
                            <input type="text" name="widget_title" value="{{ old('widget_title', $ws->widget_title) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Button Text</label>
                            <input type="text" name="button_text" value="{{ old('button_text', $ws->button_text) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Primary Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="primary_color" value="{{ old('primary_color', $ws->primary_color) }}"
                                    class="w-10 h-9 rounded border border-gray-300 cursor-pointer">
                                <input type="text" id="colorHex" value="{{ old('primary_color', $ws->primary_color) }}"
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-indigo-500"
                                    readonly>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Default Country Code</label>
                            <select name="default_country_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                @foreach([
                                    'IN' => 'India (+91)', 'US' => 'USA (+1)', 'GB' => 'UK (+44)',
                                    'AE' => 'UAE (+971)', 'SG' => 'Singapore (+65)',
                                    'AU' => 'Australia (+61)', 'DE' => 'Germany (+49)', 'FR' => 'France (+33)',
                                ] as $code => $label)
                                <option value="{{ $code }}" @selected(old('default_country_code', $ws->default_country_code) === $code)>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Thank You Message</label>
                        <textarea name="thank_you_message" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200"
                            placeholder="Thank you! We look forward to welcoming you.">{{ old('thank_you_message', $ws->thank_you_message) }}</textarea>
                    </div>
                    <div class="mt-4 flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                            <input type="checkbox" name="show_room_photos" value="1" @checked(old('show_room_photos', $ws->show_room_photos))
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-200">
                            Show Room Photos
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                            <input type="checkbox" name="show_prices" value="1" @checked(old('show_prices', $ws->show_prices))
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-200">
                            Show Prices
                        </label>
                    </div>
                </div>

                {{-- Booking Rules --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h2 class="font-semibold text-gray-800 mb-4">Booking Rules</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Min Advance (hours)</label>
                            <input type="number" name="min_advance_hours" min="0" max="168"
                                value="{{ old('min_advance_hours', $ws->min_advance_hours) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Max Advance (days)</label>
                            <input type="number" name="max_advance_days" min="1" max="730"
                                value="{{ old('max_advance_days', $ws->max_advance_days) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                            <input type="checkbox" name="auto_confirm" value="1" @checked(old('auto_confirm', $ws->auto_confirm))
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-200">
                            <span>
                                <span class="font-medium">Auto-Confirm Bookings</span>
                                <span class="text-gray-400 text-xs ml-1">(Skip website_pending status)</span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h2 class="font-semibold text-gray-800 mb-1">Advance Payment (UPI)</h2>
                    <p class="text-xs text-gray-400 mb-4">Guest will see UPI QR code on the confirmation page.</p>

                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 mb-4">
                        <input type="checkbox" name="require_advance_payment" value="1"
                            @checked(old('require_advance_payment', $ws->require_advance_payment))
                            id="requirePaymentCheck"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-200">
                        <span class="font-medium">Require Advance Payment</span>
                    </label>

                    <div id="paymentFields" class="{{ $ws->require_advance_payment ? '' : 'hidden' }}">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Advance Amount (₹)</label>
                                <input type="number" name="advance_payment_amount" min="0" step="0.01"
                                    value="{{ old('advance_payment_amount', $ws->advance_payment_amount) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">UPI ID</label>
                                <input type="text" name="upi_id" value="{{ old('upi_id', $ws->upi_id) }}"
                                    placeholder="yourname@upi"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">UPI QR Code Image</label>
                            @if($ws->upi_qr_image)
                            <img src="{{ Storage::url($ws->upi_qr_image) }}" class="w-24 h-24 object-contain rounded border mb-2">
                            @endif
                            <input type="file" name="upi_qr_image" accept="image/*"
                                class="text-sm text-gray-600">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- Sidebar: Embed Codes & Stats --}}
        <div class="space-y-5">

            {{-- Pending bookings --}}
            @if($pendingCount > 0)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-sm font-bold text-amber-800">
                    {{ $pendingCount }} Website Booking{{ $pendingCount > 1 ? 's' : '' }} Pending
                </p>
                <p class="text-xs text-amber-600 mt-0.5">Confirm and assign rooms from the Bookings page.</p>
                <a href="{{ route('admin.bookings.index') }}?status=website_pending"
                   class="mt-2 inline-block text-xs font-semibold text-amber-700 underline">View pending →</a>
            </div>
            @endif

            {{-- Embed codes --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Embed on Your Website</h2>

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Script Embed (floating button)</p>
                <div class="bg-gray-50 rounded-lg px-3 py-2.5 font-mono text-xs text-gray-700 break-all mb-2 select-all">
                    &lt;script src="{{ url("/widget/{$slug}/embed.js") }}"&gt;&lt;/script&gt;
                </div>
                <button onclick="copyCode('scriptCode')" data-code="{{ htmlspecialchars('<script src=&quot;'.url("/widget/{$slug}/embed.js").'&quot;></script>') }}"
                    id="scriptCode"
                    class="w-full text-center text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-lg py-1.5 transition mb-4">
                    Copy Script Tag
                </button>

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">iFrame Embed</p>
                <div class="bg-gray-50 rounded-lg px-3 py-2.5 font-mono text-xs text-gray-700 break-all mb-2 select-all">
                    &lt;iframe src="{{ url("/book/{$slug}/iframe") }}" width="100%" height="700" frameborder="0" style="border-radius:16px"&gt;&lt;/iframe&gt;
                </div>
                <button onclick="copyCode('iframeCode')" data-code="{{ htmlspecialchars('<iframe src=&quot;'.url("/book/{$slug}/iframe").'&quot; width=&quot;100%&quot; height=&quot;700&quot; frameborder=&quot;0&quot; style=&quot;border-radius:16px&quot;></iframe>') }}"
                    id="iframeCode"
                    class="w-full text-center text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-lg py-1.5 transition mb-4">
                    Copy iFrame Code
                </button>

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Direct Booking Page</p>
                <a href="{{ url("/book/{$slug}") }}" target="_blank"
                    class="block text-xs text-indigo-600 hover:underline break-all mb-1">
                    {{ url("/book/{$slug}") }}
                </a>
                <a href="{{ url("/book/{$slug}") }}" target="_blank"
                    class="w-full text-center text-xs font-semibold text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg py-1.5 transition block">
                    Open Booking Page ↗
                </a>
            </div>

            {{-- Quick Tips --}}
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                <h3 class="text-sm font-bold text-blue-800 mb-2">How it works</h3>
                <ul class="text-xs text-blue-700 space-y-1.5">
                    <li>• Guest selects dates and sees available room types</li>
                    <li>• Fills in details and submits booking</li>
                    <li>• Booking appears in CRM as <strong>Website Pending</strong></li>
                    <li>• You confirm and assign a room from the Bookings page</li>
                    <li>• If Auto-Confirm is on, booking is confirmed instantly</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Sync color picker with hex input
document.querySelector('input[name="primary_color"]').addEventListener('input', function() {
    document.getElementById('colorHex').value = this.value;
});

// Require advance payment toggle
document.getElementById('requirePaymentCheck').addEventListener('change', function() {
    document.getElementById('paymentFields').classList.toggle('hidden', !this.checked);
});

function copyCode(btnId) {
    const btn = document.getElementById(btnId);
    const code = btn.getAttribute('data-code').replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
    navigator.clipboard.writeText(code).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = orig, 1800);
    }).catch(() => {
        alert(code);
    });
}
</script>
@endpush
@endsection
