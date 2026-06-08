@php
    use App\Models\Vendor;
    /** @var Vendor|null $vendor */
@endphp

<div class="row gy-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Supplier name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" required maxlength="255"
            value="{{ old('name', $vendor?->name) }}">
    </div>
    <div class="col-md-6">
        <label for="contact_person" class="form-label">Contact person</label>
        <input type="text" name="contact_person" id="contact_person" class="form-control" maxlength="100"
            value="{{ old('contact_person', $vendor?->contact_person) }}">
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" id="phone" class="form-control" maxlength="10" inputmode="numeric" required
            value="{{ old('phone', $vendor?->phone) }}">
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control" maxlength="255"
            value="{{ old('email', $vendor?->email) }}" autocomplete="email">
    </div>
    <div class="col-md-6">
        <label for="gstin" class="form-label">GSTIN</label>
        <input type="text" name="gstin" id="gstin" class="form-control text-uppercase" maxlength="15"
            value="{{ old('gstin', $vendor?->gstin) }}" placeholder="Optional">
    </div>
    <div class="col-md-6">
        <label for="pan" class="form-label">PAN</label>
        <input type="text" name="pan" id="pan" class="form-control text-uppercase" maxlength="10"
            value="{{ old('pan', $vendor?->pan) }}" placeholder="Optional">
    </div>
    <div class="col-12">
        <label for="address_line1" class="form-label">Address line 1 <span class="text-danger">*</span></label>
        <input type="text" name="address_line1" id="address_line1" class="form-control" required maxlength="255"
            value="{{ old('address_line1', $vendor?->address_line1) }}">
    </div>
    <div class="col-12">
        <label for="address_line2" class="form-label">Address line 2</label>
        <input type="text" name="address_line2" id="address_line2" class="form-control" maxlength="255"
            value="{{ old('address_line2', $vendor?->address_line2) }}">
    </div>
    <div class="col-md-4">
        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
        <input type="text" name="city" id="city" class="form-control" required maxlength="100"
            value="{{ old('city', $vendor?->city) }}">
    </div>
    <div class="col-md-4">
        <label for="state_code" class="form-label">State (GST code) <span class="text-danger">*</span></label>
        <select name="state_code" id="state_code" class="form-control" required>
            <option value="">Select state</option>
            @foreach ($gstStates as $code => $label)
                <option value="{{ $code }}"
                    {{ old('state_code', $vendor?->state_code) === $code ? 'selected' : '' }}>
                    {{ $code }} — {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="pincode" class="form-label">Pincode <span class="text-danger">*</span></label>
        <input type="text" name="pincode" id="pincode" class="form-control" maxlength="6" inputmode="numeric"
            required value="{{ old('pincode', $vendor?->pincode) }}">
    </div>
    <div class="col-md-6">
        <label for="payment_terms" class="form-label">Payment terms</label>
        <input type="text" name="payment_terms" id="payment_terms" class="form-control" maxlength="100"
            value="{{ old('payment_terms', $vendor?->payment_terms) }}" placeholder="e.g. Net 30">
    </div>
    <div class="col-md-6">
        <label for="vendor_type" class="form-label">Vendor type</label>
        <select name="vendor_type" id="vendor_type" class="form-select">
            @foreach (['SUPPLIER', 'SERVICE', 'BOTH'] as $type)
                <option value="{{ $type }}" @selected(old('vendor_type', $vendor?->vendor_type ?? 'SUPPLIER') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="credit_limit" class="form-label">Credit limit</label>
        <input type="number" step="0.01" name="credit_limit" id="credit_limit" class="form-control"
            value="{{ old('credit_limit', $vendor?->credit_limit) }}">
    </div>
    <div class="col-md-4">
        <label for="bank_name" class="form-label">Bank name</label>
        <input type="text" name="bank_name" id="bank_name" class="form-control" maxlength="100"
            value="{{ old('bank_name', $vendor?->bank_name) }}">
    </div>
    <div class="col-md-4">
        <label for="bank_account_no" class="form-label">Bank account</label>
        <input type="text" name="bank_account_no" id="bank_account_no" class="form-control" maxlength="20"
            value="{{ old('bank_account_no', $vendor?->bank_account_no) }}">
    </div>
    <div class="col-md-4">
        <label for="bank_ifsc" class="form-label">IFSC</label>
        <input type="text" name="bank_ifsc" id="bank_ifsc" class="form-control text-uppercase" maxlength="11"
            value="{{ old('bank_ifsc', $vendor?->bank_ifsc) }}">
    </div>
    <div class="col-md-6">
        <label for="rating" class="form-label">Rating (0–5)</label>
        <input type="number" step="0.01" min="0" max="5" name="rating" id="rating" class="form-control"
            value="{{ old('rating', $vendor?->rating ?? 0) }}">
    </div>
    @if ($vendor !== null)
        <div class="col-12">
            <hr class="my-2">
            <h6 class="fw-semibold mb-2">Vendor portal</h6>
            <p class="text-muted small mb-2">Login credentials are emailed to the vendor address above when the portal is enabled or the password is reset.</p>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="portal_enabled" id="portal_enabled" value="1"
                    {{ old('portal_enabled', $vendor->portal_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="portal_enabled">Enable vendor self-service portal</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="generate_portal_password"
                    id="generate_portal_password" value="1"
                    {{ old('generate_portal_password') ? 'checked' : '' }}>
                <label class="form-check-label" for="generate_portal_password">Generate new portal password on save</label>
            </div>
        </div>
        <div class="col-md-6">
            <label for="portal_password" class="form-label">Portal password (optional)</label>
            <input type="password" name="portal_password" id="portal_password" class="form-control" minlength="8"
                maxlength="64" autocomplete="new-password" placeholder="Leave blank to keep current">
        </div>
    @endif
    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" maxlength="5000">{{ old('notes', $vendor?->notes) }}</textarea>
    </div>
</div>
