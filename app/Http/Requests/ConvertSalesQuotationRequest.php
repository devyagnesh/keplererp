<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertSalesQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('convert', $this->route('sales_quotation')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'credit_limit_override' => ['sometimes', 'boolean'],
        ];
    }
}
