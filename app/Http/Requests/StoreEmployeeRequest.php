<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emp_code' => ['required', 'string', 'max:32', Rule::unique('employees', 'emp_code')],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:80'],
            'designation' => ['nullable', 'string', 'max:80'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'join_date' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['sometimes', 'in:0,1,true,false'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'pf_number' => ['nullable', 'string', 'max:22'],
            'uan' => ['nullable', 'string', 'max:20'],
            'esi_number' => ['nullable', 'string', 'max:20'],
            'pf_opted_in' => ['sometimes', 'boolean'],
            'bank_account_no' => ['nullable', 'string', 'max:30'],
            'bank_ifsc' => ['nullable', 'string', 'max:11'],
            'allowances' => ['nullable', 'array'],
            'allowances.*.allowance_type_id' => ['required_with:allowances', 'integer', 'exists:allowance_types,id'],
            'allowances.*.monthly_amount' => ['required_with:allowances', 'numeric', 'min:0'],
        ];
    }
}
