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
                <div style="width:42px;height:42px;border-radius:10px;background:{{ str_contains($doc->file_type ?? '','pdf') ? '#fee2e2' : '#dbeafe' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas {{ str_contains($doc->file_type ?? '','pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500' }}" style="font-size:18px;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-weight:700;font-size:13px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $doc->document_type }}</div>
                    @if($doc->document_number)
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">No: {{ $doc->document_number }}</div>
                    @endif
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $doc->created_at->format('d M Y') }}</div>
                </div>
            </div>
            <div style="font-size:11px;color:#94a3b8;margin-bottom:14px;">{{ $doc->file_name }} &nbsp;({{ number_format($doc->file_size / 1024, 1) }} KB)</div>
            @if($doc->notes)
            <div style="font-size:12px;color:#64748b;background:#f8fafc;border-radius:8px;padding:8px 10px;margin-bottom:12px;">{{ $doc->notes }}</div>
            @endif
            <div style="display:flex;gap:8px;">
                <a href="{{ route('documents.download', $doc->id) }}" style="flex:1;text-align:center;background:#dbeafe;color:#1d4ed8;padding:8px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-download mr-1"></i>Download
                </a>
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
