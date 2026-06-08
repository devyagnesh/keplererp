@php
    use App\Models\Customer;
    /** @var Customer|null $customer */
@endphp

<div class="row gy-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Customer name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" required maxlength="255"
            value="{{ old('name', $customer?->name) }}">
    </div>
    <div class="col-md-6">
        <label for="contact_person" class="form-label">Contact person</label>
        <input type="text" name="contact_person" id="contact_person" class="form-control" maxlength="100"
            value="{{ old('contact_person', $customer?->contact_person) }}">
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" id="phone" class="form-control" maxlength="10" inputmode="numeric" required
            value="{{ old('phone', $customer?->phone) }}">
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control" maxlength="255"
            value="{{ old('email', $customer?->email) }}" autocomplete="email">
    </div>
    <div class="col-md-6">
        <label for="gstin" class="form-label">GSTIN</label>
        <input type="text" name="gstin" id="gstin" class="form-control text-uppercase" maxlength="15"
            value="{{ old('gstin', $customer?->gstin) }}" placeholder="Optional">
    </div>
    <div class="col-md-6">
        <label for="pan" class="form-label">PAN</label>
        <input type="text" name="pan" id="pan" class="form-control text-uppercase" maxlength="10"
            value="{{ old('pan', $customer?->pan) }}" placeholder="Optional">
    </div>
    <div class="col-12">
        <label for="address_line1" class="form-label">Address line 1 <span class="text-danger">*</span></label>
        <input type="text" name="address_line1" id="address_line1" class="form-control" required maxlength="255"
            value="{{ old('address_line1', $customer?->address_line1) }}">
    </div>
    <div class="col-12">
        <label for="address_line2" class="form-label">Address line 2</label>
        <input type="text" name="address_line2" id="address_line2" class="form-control" maxlength="255"
            value="{{ old('address_line2', $customer?->address_line2) }}">
    </div>
    <div class="col-md-4">
        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
        <input type="text" name="city" id="city" class="form-control" required maxlength="100"
            value="{{ old('city', $customer?->city) }}">
    </div>
    <div class="col-md-4">
        <label for="state_code" class="form-label">State (GST code) <span class="text-danger">*</span></label>
        <select name="state_code" id="state_code" class="form-control" required>
            <option value="">Select state</option>
            @foreach ($gstStates as $code => $label)
                <option value="{{ $code }}"
                    {{ old('state_code', $customer?->state_code) === $code ? 'selected' : '' }}>
                    {{ $code }} — {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="pincode" class="form-label">Pincode <span class="text-danger">*</span></label>
        <input type="text" name="pincode" id="pincode" class="form-control" maxlength="6" inputmode="numeric"
            required value="{{ old('pincode', $customer?->pincode) }}">
    </div>
    <div class="col-md-6">
        <label for="payment_terms" class="form-label">Payment terms</label>
        <input type="text" name="payment_terms" id="payment_terms" class="form-control" maxlength="100"
            value="{{ old('payment_terms', $customer?->payment_terms) }}" placeholder="e.g. Net 30">
    </div>
    @isset($priceLists)
        <div class="col-md-6">
            <label for="price_list_id" class="form-label">Price list</label>
            <select name="price_list_id" id="price_list_id" class="form-select">
                <option value="">Default / manual pricing</option>
                @foreach ($priceLists as $pl)
                    <option value="{{ $pl->id }}" @selected((string) old('price_list_id', $customer?->price_list_id) === (string) $pl->id)>
                        {{ $pl->code }} — {{ $pl->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" maxlength="5000">{{ old('notes', $customer?->notes) }}</textarea>
    </div>
</div>
