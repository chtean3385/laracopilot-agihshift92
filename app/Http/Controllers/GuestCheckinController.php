<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Customer;
use App\Models\GuestCheckinRequest;
use App\Models\Setting;
use Illuminate\Http\Request;

class GuestCheckinController extends Controller
{
    public function show(string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $settings = Setting::where('hotel_id', $hotel->id)->first();
        return view('guest.checkin', compact('hotel', 'settings', 'slug'));
    }

    public function lookup(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $phone = trim($request->query('phone', ''));
        if (strlen($phone) < 5) {
            return response()->json(['found' => false]);
        }
        $customer = Customer::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('phone', $phone)
            ->whereNull('deleted_at')
            ->first();

        if (!$customer) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'   => true,
            'name'    => $customer->name,
            'email'   => $customer->email ?? '',
            'address' => $customer->address ?? '',
            'id_type' => $customer->id_type ?? '',
            'id_number'=> $customer->id_number ?? '',
            'dob'     => $customer->date_of_birth?->format('Y-m-d') ?? '',
            'message' => 'Welcome back! Your details have been filled in.',
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'phone'               => 'required|string|max:30',
            'email'               => 'nullable|email|max:255',
            'id_type'             => 'nullable|string|max:100',
            'id_number'           => 'nullable|string|max:100',
            'address'             => 'nullable|string|max:500',
            'date_of_birth'       => 'nullable|date',
            'id_document'         => 'required|file|mimes:jpg,jpeg,png,pdf,heic,heif|max:5120',
            'signature_data'      => 'required|string',
            'requested_check_in'  => 'nullable|date',
            'requested_check_out' => 'nullable|date|after_or_equal:requested_check_in',
            'guests_count'        => 'nullable|integer|min:1|max:50',
            'additional_guests'   => 'nullable|array',
            'additional_guests.*.name'      => 'required_with:additional_guests|string|max:255',
            'additional_guests.*.id_type'   => 'nullable|string|max:100',
            'additional_guests.*.id_number' => 'nullable|string|max:100',
        ]);

        // Store the uploaded ID document
        $docPath = $request->file('id_document')->store('guest-checkin-docs/' . $hotel->id, 'public');

        GuestCheckinRequest::create([
            'hotel_id'            => $hotel->id,
            'name'                => $validated['name'],
            'phone'               => $validated['phone'],
            'email'               => $validated['email'] ?? null,
            'id_type'             => $validated['id_type'] ?? null,
            'id_number'           => $validated['id_number'] ?? null,
            'address'             => $validated['address'] ?? null,
            'date_of_birth'       => $validated['date_of_birth'] ?? null,
            'id_document_path'    => $docPath,
            'signature_data'      => $validated['signature_data'],
            'additional_guests'   => $validated['additional_guests'] ?? null,
            'requested_check_in'  => $validated['requested_check_in'] ?? null,
            'requested_check_out' => $validated['requested_check_out'] ?? null,
            'guests_count'        => $validated['guests_count'] ?? 1,
            'status'              => 'pending',
        ]);

        $settings = Setting::where('hotel_id', $hotel->id)->first();
        return view('guest.checkin-success', compact('hotel', 'settings', 'validated'));
    }
}
