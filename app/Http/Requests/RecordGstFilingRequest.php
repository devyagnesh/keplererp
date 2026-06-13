<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordGstFilingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.reports.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'gstr1_arn' => ['nullable', 'string', 'max:64'],
            'gstr3b_arn' => ['nullable', 'string', 'max:64'],
            'gstr3b_tax_paid' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
