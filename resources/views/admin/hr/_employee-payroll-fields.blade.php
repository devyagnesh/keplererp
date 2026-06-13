@php
    $employeeAllowances = isset($employee)
        ? $employee->employeeAllowances->keyBy('allowance_type_id')
        : collect();
@endphp

<hr class="my-4">
<h5 class="mb-3">Salary &amp; statutory</h5>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Basic salary (monthly)</label>
        <input type="number" step="0.01" name="basic_salary" class="form-control" required min="0"
            value="{{ old('basic_salary', $employee->basic_salary ?? '0') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">PF number</label>
        <input type="text" name="pf_number" class="form-control" maxlength="22"
            value="{{ old('pf_number', $employee->pf_number ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">UAN</label>
        <input type="text" name="uan" class="form-control" maxlength="20"
            value="{{ old('uan', $employee->uan ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">ESI number</label>
        <input type="text" name="esi_number" class="form-control" maxlength="20"
            value="{{ old('esi_number', $employee->esi_number ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Bank account</label>
        <input type="text" name="bank_account_no" class="form-control" maxlength="30"
            value="{{ old('bank_account_no', $employee->bank_account_no ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">IFSC</label>
        <input type="text" name="bank_ifsc" class="form-control" maxlength="11"
            value="{{ old('bank_ifsc', $employee->bank_ifsc ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Monthly TDS deduction</label>
        <input type="number" step="0.01" name="monthly_tds" class="form-control" min="0"
            value="{{ old('monthly_tds', $employee->monthly_tds ?? '0') }}">
    </div>
</div>

<input type="hidden" name="create_login" value="0">
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="create_login" value="1" id="empCreateLogin"
        {{ old('create_login') ? 'checked' : '' }}>
    <label class="form-check-label" for="empCreateLogin">Create staff portal login (email required)</label>
</div>

<input type="hidden" name="pf_opted_in" value="0">
<div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" name="pf_opted_in" value="1" id="pfOptIn"
        {{ old('pf_opted_in', $employee->pf_opted_in ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="pfOptIn">Opted in for PF (required above wage ceiling if rules allow)</label>
</div>

<h6 class="mb-2">Monthly allowances</h6>
<p class="text-muted fs-13">Amounts use allowance types defined under HR → Allowance types.</p>
<div class="table-responsive mb-3">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Allowance</th>
                <th style="width: 180px">Monthly amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allowanceTypes as $type)
                @php
                    $row = $employeeAllowances->get($type->id);
                    $amount = old('allowances.'.$loop->index.'.monthly_amount', $row?->monthly_amount ?? '');
                @endphp
                <tr>
                    <td>
                        {{ $type->name }} <span class="text-muted">({{ $type->code }})</span>
                        <input type="hidden" name="allowances[{{ $loop->index }}][allowance_type_id]"
                            value="{{ $type->id }}">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control"
                            name="allowances[{{ $loop->index }}][monthly_amount]" value="{{ $amount }}">
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-muted">No allowance types yet. Add them under HR → Allowance types.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
