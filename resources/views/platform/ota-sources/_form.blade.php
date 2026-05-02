@php $fieldStyle = "width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;color:#1e293b;box-sizing:border-box;outline:none;"; @endphp

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">OTA Name <span style="color:#ef4444;">*</span></label>
    <input type="text" name="name" required placeholder="e.g. Booking.com" style="{{ $fieldStyle }}">
</div>

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">Sender WhatsApp Number</label>
    <input type="text" name="sender_number" placeholder="e.g. 917043069225 (with country code, no +)" style="{{ $fieldStyle }}">
    <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Include country code without + sign. Leave empty if not yet known.</p>
</div>

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">WABA ID</label>
    <input type="text" name="waba_id" placeholder="Meta WABA ID (optional)" style="{{ $fieldStyle }}">
</div>

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">Message Pattern <span style="color:#ef4444;">*</span></label>
    <select name="message_pattern_key" style="{{ $fieldStyle }}cursor:pointer;">
        <option value="generic">generic — Standard test format</option>
        <option value="booking_com">booking_com — Booking.com</option>
        <option value="airbnb">airbnb — Airbnb</option>
        <option value="agoda">agoda — Agoda</option>
        <option value="makemytrip">makemytrip — MakeMyTrip</option>
        <option value="goibibo">goibibo — Goibibo</option>
        <option value="expedia">expedia — Expedia</option>
    </select>
</div>

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">Notes</label>
    <textarea name="notes" rows="2" placeholder="Internal notes..." style="{{ $fieldStyle }}resize:vertical;"></textarea>
</div>

<label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;">
    <input type="checkbox" name="is_active" value="1" checked style="width:16px;height:16px;accent-color:#6366f1;">
    <div>
        <div style="font-size:13px;font-weight:700;color:#1e293b;">Active</div>
        <div style="font-size:11px;color:#64748b;">Incoming messages from this sender will be processed</div>
    </div>
</label>
