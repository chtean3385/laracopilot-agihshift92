<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

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

        // Store file bytes as base64 in Neon DB — survives every deployment
        CustomerDocument::create([
            'customer_id'     => $customerId,
            'document_type'   => $request->document_type,
            'document_number' => $request->document_number,
            'file_name'       => $file->getClientOriginalName(),
            'file_path'       => '',
            'file_type'       => $file->getMimeType(),
            'file_size'       => $file->getSize(),
            'notes'           => $request->notes,
            'file_content'    => base64_encode(file_get_contents($file->getRealPath())),
        ]);

        ActivityLogger::log('Uploaded', 'Document', 'Uploaded ' . $request->document_type . ' for ' . $customer->name);

        return redirect()->route('documents.index', $customerId)
            ->with('success', 'Document uploaded successfully!');
    }

    public function download($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $document = CustomerDocument::findOrFail($id);

        if (empty($document->file_content)) {
            return back()->with('error', 'This file is no longer available. Please re-upload it.');
        }

        $bytes    = base64_decode($document->file_content);
        $mimeType = $document->file_type ?: 'application/octet-stream';

        return response($bytes, 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
            'Content-Length'      => strlen($bytes),
        ]);
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $document   = CustomerDocument::findOrFail($id);
        $customerId = $document->customer_id;
        $docName    = $document->file_name;
        ActivityLogger::log('Deleted', 'Document', 'Deleted document: ' . $docName . ' (Customer ID: ' . $customerId . ')');
        $document->delete();
        return redirect()->route('documents.index', $customerId)
            ->with('success', 'Document deleted.');
    }
}
