<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index($customerId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customer  = Customer::findOrFail($customerId);
        $documents = CustomerDocument::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('admin.documents.index', compact('customer', 'documents'));
    }

    public function create($customerId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customer = Customer::findOrFail($customerId);
        return view('admin.documents.create', compact('customer'));
    }

    public function store(Request $request, $customerId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'document_type'   => 'required|string|max:100',
            'document_number' => 'nullable|string|max:100',
            'file'            => 'required|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'notes'           => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($customerId);
        $file     = $request->file('file');
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('documents/' . $customerId, $fileName, 'public');

        CustomerDocument::create([
            'customer_id'     => $customerId,
            'document_type'   => $request->document_type,
            'document_number' => $request->document_number,
            'file_name'       => $file->getClientOriginalName(),
            'file_path'       => $filePath,
            'file_type'       => $file->getClientMimeType(),
            'file_size'       => $file->getSize(),
            'notes'           => $request->notes,
        ]);

        return redirect()->route('documents.index', $customerId)
            ->with('success', 'Document uploaded successfully!');
    }

    public function download($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $document = CustomerDocument::findOrFail($id);
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'File not found on server.');
        }
        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $document   = CustomerDocument::findOrFail($id);
        $customerId = $document->customer_id;
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return redirect()->route('documents.index', $customerId)
            ->with('success', 'Document deleted.');
    }
}