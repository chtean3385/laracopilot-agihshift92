@extends('layouts.platform')

@section('title', 'Edit Plan — Platform Admin')
@section('page-title', 'Edit Plan')
@section('page-subtitle')
Editing: {{ $plan->label }} ({{ $plan->slug }})
@endsection

@section('content')

<div style="max-width:640px;">

    <a href="{{ route('platform.plans.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6d28d9;font-weight:600;text-decoration:none;margin-bottom:20px;">
        <i class="fas fa-arrow-left"></i> Back to Plans
    </a>

    <form method="POST" action="{{ route('platform.plans.update', $plan->id) }}">
        @csrf
        @method('PUT')

        {{-- Identity (read-only slug) --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:18px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 18px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-tag" style="color:#fff;font-size:12px;"></i>
                </span>
                Plan Identity
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="form-label">Slug (read-only)</label>
                    <input type="text" value="{{ $plan->slug }}" readonly
                        style="width:100%;padding:9px 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#94a3b8;background:#f8fafc;box-sizing:border-box;cursor:not-allowed;">
                    <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Slug is immutable — preserves hotel assignment history.</p>
                </div>

                <div>
                    <label class="form-label">Display Label <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="label" value="{{ old('label', $plan->label) }}" required
                        class="form-input"
                        placeholder="e.g. Pro AI">
                    @if(isset($errors) && $errors->has('label')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('label') }}</p> @endif
                </div>

                <div>
                    <label class="form-label">Accent Color <span style="color:#ef4444;">*</span></label>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="color" name="color" value="{{ old('color', $plan->color) }}"
                            style="width:44px;height:38px;padding:2px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;background:#fff;">
                        <input type="text" id="color-hex" value="{{ old('color', $plan->color) }}"
                            style="flex:1;padding:9px 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;color:#1e293b;box-sizing:border-box;"
                            oninput="document.querySelector('input[name=color]').value=this.value">
                    </div>
                    @if(isset($errors) && $errors->has('color')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('color') }}</p> @endif
                </div>

                <div>
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" min="0"
                        class="form-input" placeholder="1">
                    <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Lower = shown first on plan selection cards.</p>
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:18px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 18px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#10b981,#059669);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-rupee-sign" style="color:#fff;font-size:12px;"></i>
                </span>
                Pricing (Rs)
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="form-label">Monthly Price <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price) }}" min="0" required
                        class="form-input" placeholder="999">
                    @if(isset($errors) && $errors->has('monthly_price')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('monthly_price') }}</p> @endif
                </div>
                <div>
                    <label class="form-label">Yearly Price <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="yearly_price" value="{{ old('yearly_price', $plan->yearly_price) }}" min="0" required
                        class="form-input" placeholder="9999">
                    @if(isset($errors) && $errors->has('yearly_price')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('yearly_price') }}</p> @endif
                </div>
            </div>
        </div>

        {{-- Limits --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:18px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 18px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#06b6d4,#0891b2);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-sliders" style="color:#fff;font-size:12px;"></i>
                </span>
                Tenant Limits
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="form-label">Max Rooms <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="max_rooms" value="{{ old('max_rooms', $plan->max_rooms) }}" min="1" required
                        class="form-input">
                    <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Use 9999 for unlimited.</p>
                    @if(isset($errors) && $errors->has('max_rooms')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('max_rooms') }}</p> @endif
                </div>
                <div>
                    <label class="form-label">Max Users <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="max_users" value="{{ old('max_users', $plan->max_users) }}" min="1" required
                        class="form-input">
                    <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Use 9999 for unlimited.</p>
                    @if(isset($errors) && $errors->has('max_users')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('max_users') }}</p> @endif
                </div>
            </div>
        </div>

        {{-- Features --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:18px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 18px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-list-check" style="color:#fff;font-size:12px;"></i>
                </span>
                Features List
            </h2>
            <label class="form-label">One feature per line</label>
            <textarea name="features" rows="6" class="form-input"
                placeholder="Guest management&#10;Room management&#10;Booking & check-in/out">{{ old('features', implode("\n", $plan->features ?? [])) }}</textarea>
            <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Each line becomes a bullet point on the plan card.</p>
        </div>

        {{-- Status --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:24px;">
            <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;background:linear-gradient(135deg,#10b981,#059669);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-toggle-on" style="color:#fff;font-size:12px;"></i>
                </span>
                Plan Status
            </h2>

            <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                    style="width:18px;height:18px;accent-color:#7c3aed;cursor:pointer;">
                <div>
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">Plan is active</div>
                    <div style="font-size:12px;color:#64748b;">Inactive plans are hidden from the hotel create/edit forms. Existing hotel assignments are preserved.</div>
                </div>
            </label>
        </div>

        <div style="display:flex;gap:12px;align-items:center;">
            <button type="submit" class="btn-primary" style="padding:12px 28px;">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="{{ route('platform.plans.index') }}" class="btn-secondary">Cancel</a>
        </div>

    </form>
</div>

@push('scripts')
<script>
document.querySelector('input[name="color"]').addEventListener('input', function() {
    document.getElementById('color-hex').value = this.value;
});
</script>
@endpush

@endsection
