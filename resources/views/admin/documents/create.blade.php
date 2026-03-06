@extends('layouts.admin')
@section('title','Upload Document')
@section('page-title','Upload Document')
@section('page-subtitle','For ' . $customer->name)
@section('content')
<div style="max-width:560px;">
    <a href="{{ route('documents.index', $customer->id) }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
            <h3 style="font-weight:700;color:#1e293b;margin:0;"><i class="fas fa-upload" style="color:#06b6d4;margin-right:8px;"></i>Upload Document for {{ $customer->name }}</h3>
        </div>
        <form action="{{ route('documents.store', $customer->id) }}" method="POST" enctype="multipart/form-data" style="padding:24px;">
            @csrf
            <div style="margin-bottom:18px;">
                <label class="form-label">Document Type <span style="color:#ef4444;">*</span></label>
                <select name="document_type" class="form-input" required>
                    <option value="">Select type</option>
                    <option value="Aadhaar Card">Aadhaar Card</option>
                    <option value="Passport">Passport</option>
                    <option value="Driving License">Driving License</option>
                    <option value="Voter ID">Voter ID</option>
                    <option value="PAN Card">PAN Card</option>
                    <option value="Visa">Visa</option>
                    <option value="Other">Other</option>
                </select>
                @error('document_type')<p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div style="margin-bottom:18px;">
                <label class="form-label">Document Number</label>
                <input type="text" name="document_number" value="{{ old('document_number') }}" class="form-input" placeholder="Optional">
            </div>
            <div style="margin-bottom:18px;">
                <label class="form-label">File <span style="color:#ef4444;">*</span></label>
                <input type="file" name="file" class="form-input" accept=".jpg,.jpeg,.png,.pdf" required style="padding:8px;">
                <p style="font-size:12px;color:#94a3b8;margin-top:4px;">Allowed: JPG, PNG, PDF. Max 5MB.</p>
                @error('file')<p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div style="margin-bottom:24px;">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-input" placeholder="Optional notes..."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid #f1f5f9;">
                <a href="{{ route('documents.index', $customer->id) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-upload mr-2"></i>Upload Document</button>
            </div>
        </form>
    </div>
</div>
@endsection
