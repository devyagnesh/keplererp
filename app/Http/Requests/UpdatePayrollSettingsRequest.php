<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.employee.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pf_enabled' => ['sometimes', 'boolean'],
            'pf_employee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'pf_employer_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'pf_wage_ceiling' => ['required', 'numeric', 'min:0'],
            'pf_max_monthly_contribution' => ['required', 'numeric', 'min:0'],
            'pf_allow_opt_in_above_ceiling' => ['sometimes', 'boolean'],
            'esi_enabled' => ['sometimes', 'boolean'],
            'esi_gross_ceiling' => ['required', 'numeric', 'min:0'],
            'esi_employee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'esi_employer_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'pt_enabled' => ['sometimes', 'boolean'],
            'pt_monthly_amount' => ['required', 'numeric', 'min:0'],
            'pt_min_gross' => ['required', 'numeric', 'min:0'],
        ];
    }
}
