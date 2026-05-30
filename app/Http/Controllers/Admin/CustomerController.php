<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $query = Customer::withCount('bookings');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }
        $customers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $hotelId = $this->currentHotelId();

        $request->merge(['phone' => PhoneHelper::normalize($request->input('phone'))]);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['nullable', 'email', Rule::unique('customers', 'email')->where('hotel_id', $hotelId)->whereNull('deleted_at')],
            'phone'         => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')->where('hotel_id', $hotelId)->whereNull('deleted_at')],
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'id_type'       => 'required|in:aadhaar,passport,driving_license,voter_id,pan_card,visa,other',
            'age'           => 'nullable|integer|min:1|max:120',
            'nationality'   => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
            'company_name'  => 'nullable|string|max:255',
            'gstin'         => 'nullable|string|max:15',
            'id_number'     => 'required_with:id_type|nullable|string|max:50',
            'documents.*'   => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'arrival_city'  => 'nullable|string|max:100',
            'travel_reason' => 'nullable|string|max:100',
            'dispatch_city' => 'nullable|string|max:100',
        ]);

        // Check if a soft-deleted guest exists with the same phone or email in this hotel
        $deletedGuest = $this->findDeletedGuestByPhoneOrEmail(
            $hotelId,
            $validated['phone'],
            $validated['email'] ?? null
        );

        if ($deletedGuest) {
            $matchedByPhone = ($deletedGuest->phone === $validated['phone']);
            $field          = $matchedByPhone ? 'phone number' : 'email address';
            $matchedValue   = $matchedByPhone ? $deletedGuest->phone : $deletedGuest->email;
            return redirect()->back()
                ->withInput()
                ->with('warning_deleted_guest', [
                    'message' => "A previously deleted guest \"{$deletedGuest->name}\" with the same {$field} ({$matchedValue}) exists in this hotel. Please contact Platform Admin to restore this guest instead of creating a duplicate.",
                    'name'    => $deletedGuest->name,
                    'phone'   => $deletedGuest->phone,
                ]);
        }

        $customer = Customer::create($validated);
        $this->saveDocuments($request, $customer->id, $validated['id_type']);
        ActivityLogger::log('Created', 'Guest', 'Created guest profile: ' . $customer->name . ' (' . $customer->phone . ')');
        return redirect()->route('customers.show', $customer->id)->with('success', 'Guest profile created successfully!');
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customer = Customer::with(['bookings.room', 'documents'])->findOrFail($id);
        return view('admin.customers.show', compact('customer'));
    }

    public function edit($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customer = Customer::with('documents')->findOrFail($id);
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $hotelId  = $this->currentHotelId();
        $customer  = Customer::findOrFail($id);

        $request->merge(['phone' => PhoneHelper::normalize($request->input('phone'))]);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['nullable', 'email', Rule::unique('customers', 'email')->where('hotel_id', $hotelId)->whereNull('deleted_at')->ignore($id)],
            'phone'         => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')->where('hotel_id', $hotelId)->whereNull('deleted_at')->ignore($id)],
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'id_type'       => 'required|in:aadhaar,passport,driving_license,voter_id,pan_card,visa,other',
            'age'           => 'nullable|integer|min:1|max:120',
            'nationality'   => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
            'company_name'  => 'nullable|string|max:255',
            'gstin'         => 'nullable|string|max:15',
            'id_number'     => 'required_with:id_type|nullable|string|max:50',
            'documents.*'   => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'arrival_city'  => 'nullable|string|max:100',
            'travel_reason' => 'nullable|string|max:100',
            'dispatch_city' => 'nullable|string|max:100',
        ]);
        $customer->update($validated);
        $this->saveDocuments($request, $customer->id, $validated['id_type']);
        ActivityLogger::log('Updated', 'Guest', 'Updated guest profile: ' . $customer->name);
        return redirect()->route('customers.show', $customer->id)->with('success', 'Guest profile updated!');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customer = Customer::findOrFail($id);
        $name  = $customer->name;
        $phone = $customer->phone;
        ActivityLogger::log('Deleted', 'Guest', 'Soft-deleted guest profile: ' . $name . ' (' . $phone . ')');
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Guest deleted.');
    }

    public function quickStore(Request $request)
    {
        if (!session('crm_logged_in')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $hotelId = $this->currentHotelId();
        if (!$hotelId) {
            return response()->json(['error' => 'No hotel context is active. Please select a hotel first.'], 422);
        }

        $request->merge(['phone' => PhoneHelper::normalize($request->input('phone'))]);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')->where('hotel_id', $hotelId)->whereNull('deleted_at')],
            'email'        => ['nullable', 'email', Rule::unique('customers', 'email')->where('hotel_id', $hotelId)->whereNull('deleted_at')],
            'id_type'      => 'nullable|in:aadhaar,passport,driving_license,voter_id,pan_card,visa,other',
            'id_number'    => 'nullable|string|max:50',
            'documents.*'  => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        // Check if a soft-deleted guest exists with the same phone or email in this hotel
        $deletedGuest = $this->findDeletedGuestByPhoneOrEmail(
            $hotelId,
            $validated['phone'],
            $validated['email'] ?? null
        );

        if ($deletedGuest) {
            $field = ($deletedGuest->phone === $validated['phone']) ? 'phone number' : 'email address';
            return response()->json([
                'error'          => "A previously deleted guest \"{$deletedGuest->name}\" with the same {$field} exists. Please ask the Platform Admin to restore this guest instead of creating a duplicate.",
                'deleted_guest'  => [
                    'id'    => $deletedGuest->id,
                    'name'  => $deletedGuest->name,
                    'phone' => $deletedGuest->phone,
                ],
            ], 422);
        }

        $validated['id_number']   = strtoupper(trim($validated['id_number'] ?? ''));
        $validated['country']     = 'India';
        $validated['nationality'] = 'Indian';
        $customer = Customer::create($validated);
        $this->saveDocuments($request, $customer->id, $validated['id_type'] ?? null);
        ActivityLogger::log('Created', 'Guest', 'Quick-created guest: ' . $customer->name . ' (' . $customer->phone . ')');
        return response()->json([
            'id'    => $customer->id,
            'name'  => $customer->name,
            'phone' => $customer->phone,
            'label' => $customer->name . ' — ' . $customer->phone,
        ]);
    }

    /**
     * Find a soft-deleted guest in this hotel matching the given phone or email.
     */
    private function findDeletedGuestByPhoneOrEmail(int $hotelId, string $phone, ?string $email): ?Customer
    {
        $query = Customer::withTrashed()
            ->whereNotNull('deleted_at')
            ->where('hotel_id', $hotelId)
            ->where(function ($q) use ($phone, $email) {
                $q->where('phone', $phone);
                if ($email) {
                    $q->orWhere('email', $email);
                }
            });

        return $query->first();
    }

    private function saveDocuments(Request $request, int $customerId, string $idType): void
    {
        if (!$request->hasFile('documents')) return;
        $typeMap = [
            'aadhaar'         => 'Aadhaar Card',
            'passport'        => 'Passport',
            'driving_license' => 'Driving License',
            'voter_id'        => 'Voter ID',
            'pan_card'        => 'PAN Card',
            'visa'            => 'Visa',
            'other'           => 'Other',
        ];
        $docType = $typeMap[$idType] ?? 'Other';
        foreach ($request->file('documents') as $file) {
            if (!$file || !$file->isValid()) continue;
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('documents/' . $customerId, $fileName, 'public');
            CustomerDocument::create([
                'customer_id'   => $customerId,
                'document_type' => $docType,
                'file_name'     => $file->getClientOriginalName(),
                'file_path'     => $filePath,
                'file_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
            ]);
        }
    }

    public function saveSignature(Request $request, $customerId)
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);
        $request->validate(['signature' => 'required|string']);
        $customer = \App\Models\Customer::findOrFail($customerId);
        $customer->update(['signature' => $request->signature]);
        \App\Services\ActivityLogger::log('Signature Saved', 'Guest', 'Primary guest signature saved for ' . $customer->name);
        return response()->json(['success' => true]);
    }

    private function currentHotelId(): ?int
    {
        $id = session('crm_hotel_id');
        if (!$id) $id = session('crm_sa_hotel_filter');
        return $id ? (int) $id : null;
    }
}
