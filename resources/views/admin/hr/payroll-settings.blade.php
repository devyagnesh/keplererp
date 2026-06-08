<x-layouts.app title="Payroll rules">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Payroll rules</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active">PF / ESI / PT</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <p class="text-muted fs-13 mb-4">
                These rules apply to every payroll run. Per-employee salary and allowances are set on each employee record.
            </p>
            <form id="payrollSettingsForm" novalidate>
                @csrf
                <h5 class="mb-3">Provident Fund (PF)</h5>
                <input type="hidden" name="pf_enabled" value="0">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="pf_enabled" value="1" id="pfEnabled"
                        {{ $settings->pf_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="pfEnabled">Enable PF deductions</label>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Employee rate (e.g. 0.12 = 12%)</label>
                        <input type="number" step="0.0001" name="pf_employee_rate" class="form-control" required
                            value="{{ $settings->pf_employee_rate }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Employer rate</label>
                        <input type="number" step="0.0001" name="pf_employer_rate" class="form-control" required
                            value="{{ $settings->pf_employer_rate }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Wage ceiling (₹)</label>
                        <input type="number" step="0.01" name="pf_wage_ceiling" class="form-control" required
                            value="{{ $settings->pf_wage_ceiling }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Max monthly contribution (₹)</label>
                        <input type="number" step="0.01" name="pf_max_monthly_contribution" class="form-control" required
                            value="{{ $settings->pf_max_monthly_contribution }}">
                    </div>
                </div>
                <input type="hidden" name="pf_allow_opt_in_above_ceiling" value="0">
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="pf_allow_opt_in_above_ceiling" value="1"
                        id="pfAboveCeiling" {{ $settings->pf_allow_opt_in_above_ceiling ? 'checked' : '' }}>
                    <label class="form-check-label" for="pfAboveCeiling">Allow PF when basic is above ceiling if employee opted in</label>
                </div>

                <h5 class="mb-3">ESI</h5>
                <input type="hidden" name="esi_enabled" value="0">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="esi_enabled" value="1" id="esiEnabled"
                        {{ $settings->esi_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="esiEnabled">Enable ESI</label>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Gross ceiling (₹)</label>
                        <input type="number" step="0.01" name="esi_gross_ceiling" class="form-control" required
                            value="{{ $settings->esi_gross_ceiling }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Employee rate</label>
                        <input type="number" step="0.0001" name="esi_employee_rate" class="form-control" required
                            value="{{ $settings->esi_employee_rate }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Employer rate</label>
                        <input type="number" step="0.0001" name="esi_employer_rate" class="form-control" required
                            value="{{ $settings->esi_employer_rate }}">
                    </div>
                </div>

                <h5 class="mb-3">Professional tax</h5>
                <input type="hidden" name="pt_enabled" value="0">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="pt_enabled" value="1" id="ptEnabled"
                        {{ $settings->pt_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="ptEnabled">Enable professional tax</label>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Monthly amount (₹)</label>
                        <input type="number" step="0.01" name="pt_monthly_amount" class="form-control" required
                            value="{{ $settings->pt_monthly_amount }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Apply when gross ≥ (₹)</label>
                        <input type="number" step="0.01" name="pt_min_gross" class="form-control" required
                            value="{{ $settings->pt_min_gross }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="payrollSettingsSubmit">Save rules</button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.payrollSettingsSubmitUrl = @json(route('admin.hr.payroll-settings.update'));
        </script>
        <script src="{{ asset('js/modules/erp/payroll-settings-form.js') }}"></script>
    @endpush
</x-layouts.app>
