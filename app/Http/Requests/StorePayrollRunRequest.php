<?php

namespace App\Http\Requests;

use App\Models\PayrollRun;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PayrollRun::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => [
                'required',
                'integer',
                'min:1',
                'max:12',
                Rule::unique('payroll_runs', 'period_month')->where(
                    'period_year',
                    (int) $this->input('period_year')
                ),
            ],
        ];
    }
}
