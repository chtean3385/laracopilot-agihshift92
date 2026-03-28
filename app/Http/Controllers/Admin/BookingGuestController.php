<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class BookingGuestController extends Controller
{
    public function store(Request $request, $bookingId)
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $booking = Booking::findOrFail($bookingId);

        $validated = $request->validate([
            'name'        => 'required|string|max:120',
            'age'         => 'nullable|integer|min:0|max:120',
            'gender'      => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:80',
            'id_type'     => 'nullable|string|max:40',
            'id_number'   => 'nullable|string|max:60',
            'dob'         => 'nullable|date',
            'relation'    => 'nullable|string|max:40',
            'notes'       => 'nullable|string|max:255',
            'document'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $guestData = $validated;
        unset($guestData['document']);
        
        $guest = BookingGuest::create(array_merge($guestData, ['booking_id' => $bookingId]));

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('guest-docs/' . $bookingId, 'public');
            $guest->update([
                'id_document_path' => $path,
                'id_document_name' => $file->getClientOriginalName(),
            ]);
        }

        ActivityLogger::log('Added Guest', 'Booking', 'Added guest ' . $validated['name'] . ' to Booking #' . $booking->booking_number);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'guest' => $guest]);
        }

        return back()->with('success', 'Guest added successfully.');
    }

    public function destroy($bookingId, $guestId)
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $guest = BookingGuest::where('booking_id', $bookingId)->findOrFail($guestId);
        $booking = Booking::findOrFail($bookingId);
        $name = $guest->name;
        $guest->delete();

        ActivityLogger::log('Removed Guest', 'Booking', 'Removed guest ' . $name . ' from Booking #' . $booking->booking_number);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Guest removed.');
    }

    public function saveSignature(Request $request, $bookingId, $guestId)
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $request->validate(['signature' => 'required|string']);

        $guest = BookingGuest::where('booking_id', $bookingId)->findOrFail($guestId);
        $guest->update(['signature' => $request->signature]);

        ActivityLogger::log('Signature Saved', 'Booking', 'Signature saved for guest ' . $guest->name);

        return response()->json(['success' => true]);
    }

    public function uploadDoc(Request $request, $bookingId, $guestId)
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $request->validate(['document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120']);

        $guest = BookingGuest::where('booking_id', $bookingId)->findOrFail($guestId);

        if ($guest->id_document_path && \Storage::disk('public')->exists($guest->id_document_path)) {
            \Storage::disk('public')->delete($guest->id_document_path);
        }

        $file = $request->file('document');
        $path = $file->store('guest-docs/' . $bookingId, 'public');

        $guest->update([
            'id_document_path' => $path,
            'id_document_name' => $file->getClientOriginalName(),
        ]);

        ActivityLogger::log('Document Uploaded', 'Booking', 'ID document uploaded for guest ' . $guest->name);

        return response()->json(['success' => true, 'file_name' => $file->getClientOriginalName()]);
    }

    public function downloadDoc($bookingId, $guestId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $guest = BookingGuest::where('booking_id', $bookingId)->findOrFail($guestId);

        if (!$guest->id_document_path || !\Storage::disk('public')->exists($guest->id_document_path)) {
            return back()->with('error', 'Document not found.');
        }

        return \Storage::disk('public')->download($guest->id_document_path, $guest->id_document_name ?? 'document');
    }
}
