<?php

namespace App\Http\Requests;

use App\Models\SalesQuotation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalesQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SalesQuotation::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->filter(fn (mixed $line): bool => is_array($line)
                && ! empty($line['item_id'])
                && isset($line['quantity']) && $line['quantity'] !== ''
                && isset($line['unit_price']) && $line['unit_price'] !== '')
            ->values()
            ->all();
        $this->merge(['lines' => $lines]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quote_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
