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
        <form id="docUploadForm" action="{{ route('documents.store', $customer->id) }}" method="POST" enctype="multipart/form-data" style="padding:24px;">
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
                <input type="file" id="docFileInput" name="file" class="form-input" accept=".jpg,.jpeg,.png,.pdf" required style="padding:8px;">
                <p style="font-size:12px;color:#94a3b8;margin-top:4px;">Allowed: JPG, PNG, PDF. Max 1 MB. Images are auto-compressed.</p>
                <div id="fileSizeInfo" style="display:none;margin-top:8px;padding:8px 12px;border-radius:8px;font-size:12px;"></div>
                @error('file')<p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <div style="margin-bottom:24px;">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-input" placeholder="Optional notes..."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid #f1f5f9;">
                <a href="{{ route('documents.index', $customer->id) }}" class="btn-secondary">Cancel</a>
                <button type="submit" id="uploadBtn" class="btn-primary"><i class="fas fa-upload mr-2"></i>Upload Document</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const input  = document.getElementById('docFileInput');
    const info   = document.getElementById('fileSizeInfo');
    const btn    = document.getElementById('uploadBtn');
    const form   = document.getElementById('docUploadForm');
    const MAX_B  = 1024 * 1024; // 1 MB
    const MAX_PX = 1400;        // max image dimension after resize
    let compressedFile = null;

    function fmt(bytes) {
        return bytes < 1024 ? bytes + ' B'
             : bytes < 1048576 ? (bytes / 1024).toFixed(1) + ' KB'
             : (bytes / 1048576).toFixed(2) + ' MB';
    }

    function showInfo(html, color) {
        var colors = {
            green:  { bg: '#f0fdf4', border: '#bbf7d0', text: '#166534' },
            red:    { bg: '#fef2f2', border: '#fecaca', text: '#991b1b' },
            yellow: { bg: '#fffbeb', border: '#fde68a', text: '#92400e' },
        };
        var c = colors[color] || colors.yellow;
        info.style.cssText = 'display:block;margin-top:8px;padding:8px 12px;border-radius:8px;font-size:12px;'
            + 'background:' + c.bg + ';border:1px solid ' + c.border + ';color:' + c.text;
        info.innerHTML = html;
    }

    function compress(file, quality, done) {
        var img = new Image();
        var url = URL.createObjectURL(file);
        img.onload = function () {
            URL.revokeObjectURL(url);
            var w = img.width, h = img.height;
            if (w > MAX_PX || h > MAX_PX) {
                if (w >= h) { h = Math.round(h * MAX_PX / w); w = MAX_PX; }
                else        { w = Math.round(w * MAX_PX / h); h = MAX_PX; }
            }
            var canvas = document.createElement('canvas');
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            canvas.toBlob(function (blob) { done(blob, w, h); }, 'image/jpeg', quality);
        };
        img.src = url;
    }

    function processFile(file) {
        var isImage = /^image\/(jpeg|jpg|png|webp)$/i.test(file.type);
        var orig = file.size;
        compressedFile = null;
        btn.disabled = false;

        if (!isImage) {
            // PDF — validate only
            if (orig > MAX_B) {
                showInfo('<i class="fas fa-times-circle" style="margin-right:6px;"></i>PDF is ' + fmt(orig) + ' — must be under 1 MB. Please compress it before uploading.', 'red');
                btn.disabled = true;
            } else {
                showInfo('<i class="fas fa-check-circle" style="margin-right:6px;"></i>PDF ready — ' + fmt(orig), 'green');
            }
            return;
        }

        showInfo('<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Compressing image…', 'yellow');
        btn.disabled = true;

        compress(file, 0.80, function (b1, w, h) {
            if (b1.size <= MAX_B) {
                accept(b1, orig, w, h);
            } else {
                compress(file, 0.55, function (b2) {
                    if (b2.size <= MAX_B) {
                        accept(b2, orig, w, h);
                    } else {
                        showInfo('<i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Image still too large after compression (' + fmt(b2.size) + '). Please use a smaller photo.', 'red');
                        btn.disabled = true;
                    }
                });
            }
        });
    }

    function accept(blob, origSize, w, h) {
        compressedFile = new File([blob], 'document.jpg', { type: 'image/jpeg' });
        var saving = Math.round((1 - blob.size / origSize) * 100);
        var msg = saving > 5
            ? '<i class="fas fa-check-circle" style="margin-right:6px;"></i>Compressed: '
                + fmt(origSize) + ' &rarr; <strong>' + fmt(blob.size) + '</strong>'
                + ' &nbsp;<span style="opacity:.7;">(' + saving + '% smaller, ' + w + '&times;' + h + 'px)</span>'
            : '<i class="fas fa-check-circle" style="margin-right:6px;"></i>Image ready — ' + fmt(blob.size);
        showInfo(msg, 'green');
        btn.disabled = false;
    }

    input.addEventListener('change', function () {
        var file = this.files[0];
        if (!file) { info.style.display = 'none'; compressedFile = null; btn.disabled = false; return; }
        processFile(file);
    });

    form.addEventListener('submit', function (e) {
        if (compressedFile) {
            e.preventDefault();
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading…';
            var dt = new DataTransfer();
            dt.items.add(compressedFile);
            input.files = dt.files;
            form.submit();
        } else {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading…';
        }
    });
})();
</script>
@endsection
