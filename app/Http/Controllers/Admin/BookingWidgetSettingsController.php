<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingWidgetSetting;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingWidgetSettingsController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        if (!Module::isEnabled('booking-widget')) abort(404);

        $hotelId = session('crm_hotel_id');
        $hotel   = Hotel::findOrFail($hotelId);
        $ws      = BookingWidgetSetting::where('hotel_id', $hotelId)->first()
            ?? new BookingWidgetSetting(['hotel_id' => $hotelId]);

        $pendingCount = Booking::where('status', 'website_pending')->count();
        $slug         = $hotel->slug;

        return view('admin.booking_widget.settings', compact('ws', 'hotel', 'slug', 'pendingCount'));
    }

    public function update(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        if (!Module::isEnabled('booking-widget')) abort(404);

        $hotelId = session('crm_hotel_id');

        $data = $request->validate([
            'widget_title'             => 'required|string|max:100',
            'primary_color'            => 'required|string|max:10',
            'button_text'              => 'required|string|max:50',
            'min_advance_hours'        => 'required|integer|min:0|max:168',
            'max_advance_days'         => 'required|integer|min:1|max:730',
            'auto_confirm'             => 'nullable|boolean',
            'require_advance_payment'  => 'nullable|boolean',
            'advance_payment_amount'   => 'nullable|numeric|min:0',
            'upi_id'                   => 'nullable|string|max:100',
            'default_country_code'     => 'required|string|max:5',
            'show_room_photos'         => 'nullable|boolean',
            'show_prices'              => 'nullable|boolean',
            'thank_you_message'        => 'nullable|string|max:500',
        ]);

        $data['hotel_id']                 = $hotelId;
        $data['auto_confirm']             = $request->boolean('auto_confirm');
        $data['require_advance_payment']  = $request->boolean('require_advance_payment');
        $data['show_room_photos']         = $request->boolean('show_room_photos');
        $data['show_prices']              = $request->boolean('show_prices');

        // Handle UPI QR image upload
        if ($request->hasFile('upi_qr_image')) {
            $request->validate(['upi_qr_image' => 'file|image|max:2048']);
            $path = $request->file('upi_qr_image')->store("widget/{$hotelId}", 'public');
            $data['upi_qr_image'] = $path;
        }

        BookingWidgetSetting::updateOrCreate(
            ['hotel_id' => $hotelId],
            $data
        );

        return back()->with('success', 'Booking widget settings saved.');
    }

    public function confirmBooking(Request $request, int $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $hotelId = session('crm_hotel_id');

        $booking = Booking::findOrFail($id);
        if ($booking->hotel_id !== $hotelId) abort(403);

        // Hotel-scoped room validation — prevents cross-tenant IDOR
        $request->validate([
            'room_id' => "required|integer|exists:rooms,id,hotel_id,{$hotelId}",
        ]);

        $booking->update([
            'room_id' => $request->room_id,
            'status'  => 'confirmed',
        ]);

        // Resolve conflict if exists
        DB::table('ota_booking_conflicts')
            ->where('booking_id', $id)
            ->where('resolved', false)
            ->update([
                'resolved'    => true,
                'resolved_by' => session('crm_user_id'),
                'resolved_at' => now(),
                'updated_at'  => now(),
            ]);

        return back()->with('success', 'Booking confirmed and room assigned.');
    }
}
