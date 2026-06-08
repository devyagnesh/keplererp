@php
    use App\Enums\DefaultTaxType;
    use Illuminate\Support\Facades\Storage;

    $fyDefault =
        $company?->financial_year_start?->format('Y-m-d') ??
        (now()->month >= 4
            ? now()->startOfYear()->addMonths(3)->format('Y-m-d')
            : now()->subYear()->startOfYear()->addMonths(3)->format('Y-m-d'));
    $prefixYear = now()->format('Y') . '-' . now()->addYear()->format('y');
@endphp

<x-layouts.app title="Company setup">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Company &amp; system setup</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.company.edit') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Company</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Company master</div>
                </div>
                <div class="card-body">
                    <p class="text-muted fs-13 mb-4">Configure legal entity, GST, prefixes, and integration flags. This
                        record is the foundation for all other modules.</p>

                    <form id="companyForm" method="POST" action="{{ route('admin.company.update') }}"
                        enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row gy-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="company_name" id="company_name" class="form-control"
                                    value="{{ old('company_name', $company?->company_name) }}" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label for="legal_name" class="form-label">Legal name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="legal_name" id="legal_name" class="form-control"
                                    value="{{ old('legal_name', $company?->legal_name) }}" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label for="gstin" class="form-label">GSTIN <span class="text-danger">*</span></label>
                                <input type="text" name="gstin" id="gstin" class="form-control text-uppercase"
                                    value="{{ old('gstin', $company?->gstin) }}" maxlength="15" required>
                            </div>
                            <div class="col-md-6">
                                <label for="pan" class="form-label">PAN <span class="text-danger">*</span></label>
                                <input type="text" name="pan" id="pan" class="form-control text-uppercase"
                                    value="{{ old('pan', $company?->pan) }}" maxlength="10" required>
                            </div>
                            <div class="col-12">
                                <label for="address_line1" class="form-label">Address line 1 <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="address_line1" id="address_line1" class="form-control"
                                    value="{{ old('address_line1', $company?->address_line1) }}" required maxlength="255">
                            </div>
                            <div class="col-12">
                                <label for="address_line2" class="form-label">Address line 2</label>
                                <input type="text" name="address_line2" id="address_line2" class="form-control"
                                    value="{{ old('address_line2', $company?->address_line2) }}" maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" name="city" id="city" class="form-control"
                                    value="{{ old('city', $company?->city) }}" required maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="state_code" class="form-label">State (GST code) <span
                                        class="text-danger">*</span></label>
                                <select name="state_code" id="state_code" class="form-control" required>
                                    <option value="">Select state</option>
                                    @foreach ($gstStates as $code => $label)
                                        <option value="{{ $code }}"
                                            {{ old('state_code', $company?->state_code) === $code ? 'selected' : '' }}>
                                            {{ $code }} — {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="pincode" class="form-label">Pincode <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" id="pincode" class="form-control"
                                    value="{{ old('pincode', $company?->pincode) }}" maxlength="6" inputmode="numeric"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control"
                                    value="{{ old('phone', $company?->phone) }}" maxlength="10" inputmode="numeric"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control"
                                    value="{{ old('email', $company?->email) }}" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label for="logo" class="form-label">Logo</label>
                                <input type="file" name="logo" id="logo" class="form-control"
                                    accept="image/jpeg,image/png">
                                <span class="fs-12 text-muted">JPG or PNG, max 2&nbsp;MB.</span>
                                @if ($company?->logo)
                                    <div class="mt-2">
                                        <img src="{{ Storage::disk('public')->url($company->logo) }}" alt="Company logo"
                                            class="avatar avatar-xxl border rounded p-1 bg-white">
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="financial_year_start" class="form-label">Financial year start <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="financial_year_start" id="financial_year_start"
                                    class="form-control"
                                    value="{{ old('financial_year_start', $fyDefault) }}" required
                                    placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                <input type="text" name="currency" id="currency" class="form-control text-uppercase"
                                    value="{{ old('currency', $company?->currency ?? 'INR') }}" maxlength="3" required>
                            </div>
                            <div class="col-md-4">
                                <label for="invoice_prefix" class="form-label">Invoice prefix <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="invoice_prefix" id="invoice_prefix" class="form-control"
                                    value="{{ old('invoice_prefix', $company?->invoice_prefix ?? 'INV/' . $prefixYear . '/') }}"
                                    required maxlength="20">
                            </div>
                            <div class="col-md-4">
                                <label for="po_prefix" class="form-label">PO prefix <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="po_prefix" id="po_prefix" class="form-control"
                                    value="{{ old('po_prefix', $company?->po_prefix ?? 'PO/' . $prefixYear . '/') }}"
                                    required maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label for="default_tax_type" class="form-label">Default tax type <span
                                        class="text-danger">*</span></label>
                                <select name="default_tax_type" id="default_tax_type" class="form-control" required>
                                    @foreach (DefaultTaxType::cases() as $type)
                                        <option value="{{ $type->value }}"
                                            {{ old('default_tax_type', $company?->default_tax_type?->value ?? DefaultTaxType::CGST_SGST->value) === $type->value ? 'selected' : '' }}>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100">
                                    @php
                                        $wa = (string) old('whatsapp_enabled', $company?->whatsapp_enabled ? '1' : '0');
                                        $ei = (string) old('einvoice_enabled', $company?->einvoice_enabled ? '1' : '0');
                                        $ew = (string) old('eway_enabled', $company?->eway_enabled ? '1' : '0');
                                    @endphp
                                    <input type="hidden" name="whatsapp_enabled" value="0">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_enabled"
                                            id="whatsapp_enabled" value="1" {{ $wa === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="whatsapp_enabled">WhatsApp
                                            notifications</label>
                                    </div>
                                    <p class="text-muted fs-12 mb-0">
                                        Requires Meta WhatsApp Cloud API templates (see SRS §17). Set
                                        <code>WABA_PHONE_NUMBER_ID</code>, <code>WABA_ACCESS_TOKEN</code>, and
                                        <code>WHATSAPP_DRIVER=cloud</code> in <code>.env</code>. Vendor and customer
                                        <strong>phone</strong> fields are used as WhatsApp destinations; staff use the
                                        optional WhatsApp field on user profiles for internal alerts.
                                    </p>
                                    <input type="hidden" name="einvoice_enabled" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="einvoice_enabled"
                                            id="einvoice_enabled" value="1" {{ $ei === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="einvoice_enabled">e-Invoice (IRN)
                                            enabled</label>
                                    </div>
                                    <input type="hidden" name="eway_enabled" value="0">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="eway_enabled"
                                            id="eway_enabled" value="1" {{ $ew === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="eway_enabled">e-Way bill generation
                                            enabled</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            @can('company.edit')
                                <button type="submit" class="btn btn-primary" id="companySubmit">Save company</button>
                            @else
                                <p class="text-muted fs-13 mb-0">You do not have permission to save company settings.</p>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/modules/company/edit.js') }}"></script>
    @endpush
</x-layouts.app>
