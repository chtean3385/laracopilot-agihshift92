@extends('layouts.admin')
@section('title','Documents')
@section('page-title','Guest Documents')
@section('page-subtitle',$customer->name . ' — ID Documents & Files')
@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <a href="{{ route('customers.show', $customer->id) }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back to Guest</a>
        <a href="{{ route('documents.create', $customer->id) }}" class="btn-primary text-sm"><i class="fas fa-upload mr-2"></i>Upload Document</a>
    </div>

    <div style="background:linear-gradient(135deg,#0f172a,#1e3a5f);border-radius:16px;padding:20px 24px;color:#fff;display:flex;align-items:center;gap:16px;">
        <div style="width:52px;height:52px;background:rgba(6,182,212,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-file-alt" style="font-size:22px;color:#06b6d4;"></i>
        </div>
        <div>
            <div style="font-size:18px;font-weight:800;">{{ $customer->name }}</div>
            <div style="font-size:13px;color:#94a3b8;margin-top:2px;">{{ $documents->count() }} document(s) on file &nbsp;•&nbsp; {{ strtoupper(str_replace('_',' ',$customer->id_type)) }}: {{ $customer->id_number }}</div>
        </div>
    </div>

    @if($documents->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($documents as $doc)
        <div style="background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:18px;">
            <div style="display:flex;align-items:start;gap:12px;margin-bottom:14px;">
                <div style="width:42px;height:42px;border-radius:10px;background:{{ str_contains($doc->file_type ?? '','pdf') ? '#fee2e2' : 'rgba(201,169,110,.1)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas {{ str_contains($doc->file_type ?? '','pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-image' }}" style="font-size:18px; color: {{ str_contains($doc->file_type ?? '','pdf') ? '#dc2626' : '#c9a96e' }};"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-weight:700;font-size:13px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $doc->document_type }}</div>
                    @if($doc->document_number)
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">No: {{ $doc->document_number }}</div>
                    @endif
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $doc->created_at->format('d M Y') }}</div>
                </div>
            </div>
            {{-- Image preview for JPG/PNG files --}}
            @php
                $isImage    = in_array(strtolower($doc->file_type ?? ''), ['image/jpeg','image/jpg','image/png','image/webp']);
                $hasContent = !empty($doc->file_content);
            @endphp
            @if($isImage && $hasContent)
            <div style="margin-bottom:12px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f8fafc;text-align:center;">
                <img src="{{ route('documents.download', $doc->id) }}" alt="{{ $doc->document_type }}"
                     style="max-width:100%;max-height:180px;object-fit:contain;display:block;margin:0 auto;cursor:pointer;"
                     onclick="window.open(this.src,'_blank')">
            </div>
            @elseif(!$hasContent)
            <div style="margin-bottom:12px;border-radius:8px;background:#fef9c3;border:1px solid #fde047;padding:10px 12px;font-size:12px;color:#854d0e;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-exclamation-triangle"></i>
                File no longer available — please re-upload this document.
            </div>
            @endif
            <div style="font-size:11px;color:#94a3b8;margin-bottom:14px;">{{ $doc->file_name }} &nbsp;({{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB)</div>
            @if($doc->notes)
            <div style="font-size:12px;color:#64748b;background:#f8fafc;border-radius:8px;padding:8px 10px;margin-bottom:12px;">{{ $doc->notes }}</div>
            @endif
            <div style="display:flex;gap:8px;">
                @if($hasContent)
                <a href="{{ route('documents.download', $doc->id) }}" target="_blank" style="flex:1;text-align:center;background:#dbeafe;color:#1d4ed8;padding:8px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-eye mr-1"></i>View / Download
                </a>
                @else
                <a href="{{ route('documents.create', $doc->customer_id) }}" style="flex:1;text-align:center;background:#fef9c3;color:#92400e;padding:8px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-upload mr-1"></i>Re-upload
                </a>
                @endif
                <form action="{{ route('documents.destroy', $doc->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete this document?')" style="background:#fee2e2;color:#dc2626;border:none;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div style="background:#fff;border-radius:16px;padding:60px 24px;text-align:center;border:2px dashed #e2e8f0;">
        <div style="width:64px;height:64px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-file-upload" style="font-size:24px;color:#94a3b8;"></i>
        </div>
        <h3 style="font-size:16px;font-weight:700;color:#374151;margin-bottom:6px;">No Documents Yet</h3>
        <p style="font-size:13px;color:#9ca3af;margin-bottom:16px;">Upload ID proof, passport, or any other guest documents.</p>
        <a href="{{ route('documents.create', $customer->id) }}" class="btn-primary text-sm"><i class="fas fa-upload mr-2"></i>Upload First Document</a>
    </div>
    @endif
</div>
@endsection
