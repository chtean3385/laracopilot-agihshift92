<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

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
        $customers = $query->orderBy('created_at', 'desc')->paginate(15);
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
            'id_type'       => 'required|in:aadhaar,passport,driving_license,voter_id',
            'id_number'     => 'required|string|max:50',
            'date_of_birth' => 'nullable|date',
            'nationality'   => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
        ]);
        $customer = Customer::create($validated);
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
        $customer = Customer::findOrFail($id);
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
            'id_type'       => 'required|in:aadhaar,passport,driving_license,voter_id',
            'id_number'     => 'required|string|max:50',
            'date_of_birth' => 'nullable|date',
            'nationality'   => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
        ]);
        $customer->update($validated);
        return redirect()->route('customers.show', $customer->id)->with('success', 'Guest profile updated!');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        Customer::findOrFail($id)->delete();
        return redirect()->route('customers.index')->with('success', 'Guest deleted.');
    }
}