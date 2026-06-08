<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PurchaseOrder::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['pr_id', 'warehouse_id'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
        $lines = collect($this->input('lines', []))
            ->filter(fn (mixed $line): bool => is_array($line)
                && ! empty($line['item_id'])
                && isset($line['quantity']) && $line['quantity'] !== ''
                && isset($line['unit_cost']) && $line['unit_cost'] !== '')
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
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'pr_id' => ['nullable', 'integer', 'exists:purchase_requisitions,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'expected_delivery' => ['nullable', 'date'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
