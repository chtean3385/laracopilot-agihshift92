<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'nullable|email|unique:customers,email',
            'phone'         => 'required|string|max:20',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'id_type'       => 'required|in:aadhaar,passport,driving_license,voter_id,pan_card,visa,other',
            'date_of_birth' => 'nullable|date',
            'nationality'   => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
            'documents.*'   => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);
        $validated['id_number'] = '';
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
        $customer  = Customer::findOrFail($id);
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'nullable|email|unique:customers,email,' . $id,
            'phone'         => 'required|string|max:20',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'id_type'       => 'required|in:aadhaar,passport,driving_license,voter_id,pan_card,visa,other',
            'date_of_birth' => 'nullable|date',
            'nationality'   => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
            'documents.*'   => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);
        $validated['id_number'] = $customer->id_number ?: '';
        $customer->update($validated);
        $this->saveDocuments($request, $customer->id, $validated['id_type']);
        ActivityLogger::log('Updated', 'Guest', 'Updated guest profile: ' . $customer->name);
        return redirect()->route('customers.show', $customer->id)->with('success', 'Guest profile updated!');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customer = Customer::findOrFail($id);
        $name = $customer->name;
        $customer->delete();
        ActivityLogger::log('Deleted', 'Guest', 'Deleted guest profile: ' . $name);
        return redirect()->route('customers.index')->with('success', 'Guest deleted.');
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
}
