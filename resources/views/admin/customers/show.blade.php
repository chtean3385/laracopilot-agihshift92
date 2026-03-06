@extends('layouts.admin')
@section('title', $customer->name)
@section('page-title', $customer->name)
@section('page-subtitle', 'Guest profile and booking history')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back to Guests</a>
        <div class="flex gap-3">
            <a href="{{ route('documents.index', $customer->id) }}" class="btn-secondary text-sm"><i class="fas fa-file mr-2"></i>Documents ({{ $customer->documents->count() }})</a>
            @if($customer->phone)
            <button onclick="document.getElementById('whatsapp-modal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm">
                <i class="fab fa-whatsapp text-base"></i>WhatsApp
            </button>
            @endif
            @canDo('guests.edit')
            <a href="{{ route('customers.edit', $customer->id) }}" class="btn-primary text-sm"><i class="fas fa-edit mr-2"></i>Edit Profile</a>
            @endCanDo
            @canDo('guests.delete')
            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Permanently delete this guest?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger text-sm"><i class="fas fa-trash mr-2"></i>Delete</button>
            </form>
            @endCanDo
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-3xl shadow-lg mx-auto mb-3">
                    {{ substr($customer->name, 0, 1) }}
                </div>
                <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                <p class="text-gray-400 text-sm">{{ $customer->nationality }}</p>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-phone text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700 flex-1">{{ $customer->phone }}</span>
                    @if($customer->phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $customer->phone) }}"
                       target="_blank"
                       class="flex items-center gap-1 text-green-600 hover:text-green-700 text-xs font-semibold">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    @endif
                </div>
                @if($customer->email)
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-envelope text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700">{{ $customer->email }}</span>
                </div>
                @endif
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-map-marker-alt text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700">{{ $customer->city }}, {{ $customer->state }}, {{ $customer->country }}</span>
                </div>
                @if($customer->date_of_birth)
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-birthday-cake text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700">{{ $customer->date_of_birth->format('d M Y') }}</span>
                </div>
                @endif
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-id-card text-cyan-500 w-4"></i>
                    <div>
                        <div class="text-xs text-gray-400">{{ ucwords(str_replace('_', ' ', $customer->id_type)) }}</div>
                        <div class="text-sm text-gray-700 font-medium">{{ $customer->id_number }}</div>
                    </div>
                </div>
                @if($customer->address)
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-home text-cyan-500 w-4 mt-0.5"></i>
                    <span class="text-sm text-gray-700">{{ $customer->address }}</span>
                </div>
                @endif
            </div>
            @if($customer->notes)
            <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                <p class="text-xs font-semibold text-amber-700 mb-1">Notes</p>
                <p class="text-sm text-amber-600">{{ $customer->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Booking History -->
        <div class="lg:col-span-2 space-y-5">
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <div class="text-3xl font-bold text-cyan-600">{{ $customer->bookings->count() }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Stays</div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <div class="text-3xl font-bold text-emerald-600">{{ $customer->bookings->sum('nights') }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Nights</div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <div class="text-2xl font-bold text-violet-600">₹{{ number_format($customer->bookings->sum('total_amount')) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Spent</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Booking History</h3>
                    @canDo('bookings.create')
                    <a href="{{ route('bookings.create') }}?customer_id={{ $customer->id }}" class="btn-primary text-xs"><i class="fas fa-plus mr-1"></i>New Booking</a>
                    @endCanDo
                </div>
                @if($customer->bookings->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking #</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Dates</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($customer->bookings as $booking)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-sm font-mono text-cyan-600">{{ $booking->booking_number }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">Room {{ $booking->room->room_number }}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $booking->check_in_date->format('d M Y') }} → {{ $booking->check_out_date->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700">₹{{ number_format($booking->total_amount) }}</td>
                                <td class="px-6 py-3"><span class="badge-{{ $booking->status_color }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></td>
                                <td class="px-6 py-3 text-right"><a href="{{ route('bookings.show', $booking->id) }}" class="text-cyan-600 hover:underline text-xs">View</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-10 text-gray-400">
                    <i class="fas fa-calendar-times text-3xl mb-2"></i>
                    <p class="text-sm">No bookings yet</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- WhatsApp Modal --}}
@if($customer->phone)
<div id="whatsapp-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
                    <i class="fab fa-whatsapp text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">Send WhatsApp Message</h3>
                    <p class="text-xs text-gray-400">To: {{ $customer->name }} · {{ $customer->phone }}</p>
                </div>
            </div>
            <button onclick="document.getElementById('whatsapp-modal').classList.add('hidden')"
                class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-500 transition-all">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="form-label">Message Template</label>
                <select id="wa-template" onchange="applyTemplate()" class="form-input mb-3">
                    <option value="">Select a template…</option>
                    <option value="booking_reminder">Booking Reminder</option>
                    <option value="checkin_details">Check-In Details</option>
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="checkout_reminder">Check-Out Reminder</option>
                    <option value="custom">Custom Message</option>
                </select>
            </div>
            <div class="mb-5">
                <label class="form-label">Message</label>
                <textarea id="wa-message" rows="5" class="form-input"
                    placeholder="Type your message here…">Dear {{ $customer->name }}, </textarea>
            </div>
            <div class="flex gap-3">
                <a id="wa-send-btn"
                   href="#"
                   target="_blank"
                   onclick="openWhatsApp(event)"
                   class="flex-1 inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-xl transition-all">
                    <i class="fab fa-whatsapp text-lg"></i>Open in WhatsApp
                </a>
                <button onclick="document.getElementById('whatsapp-modal').classList.add('hidden')"
                    class="btn-secondary px-6">Cancel</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const customerName = "{{ $customer->name }}";
const resortName   = "{{ $settings->resort_name ?? 'our resort' }}";
const phone        = "{{ preg_replace('/\D/', '', $customer->phone) }}";

const templates = {
    booking_reminder : `Dear ${customerName}, this is a friendly reminder from ${resortName}. Your booking is confirmed. Please don't hesitate to contact us if you need any assistance before your arrival. We look forward to welcoming you!`,
    checkin_details  : `Dear ${customerName}, your room is ready at ${resortName}! Please arrive at our reception with a valid photo ID. Check-in time is 2:00 PM. We're excited to host you!`,
    payment_reminder : `Dear ${customerName}, we noticed there is an outstanding balance on your booking at ${resortName}. Kindly settle the amount at your earliest convenience. Thank you!`,
    checkout_reminder: `Dear ${customerName}, this is a reminder that your check-out at ${resortName} is scheduled for tomorrow. Check-out time is 11:00 AM. We hope you had a wonderful stay!`,
    custom           : `Dear ${customerName}, `,
};

function applyTemplate() {
    const key = document.getElementById('wa-template').value;
    if (key && templates[key]) {
        document.getElementById('wa-message').value = templates[key];
    }
}

function openWhatsApp(e) {
    e.preventDefault();
    const msg = document.getElementById('wa-message').value.trim();
    if (!msg) { alert('Please enter a message before sending.'); return; }
    const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}
</script>
@endpush
@endif

@endsection
