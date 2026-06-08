<?php

namespace App\Http\Requests;

use App\Models\ProductionOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductionOrder::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['bom_id', 'warehouse_id'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'bom_id' => ['nullable', 'integer', 'exists:bill_of_materials,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'qty_planned' => ['required', 'numeric', 'min:0.0001'],
            'planned_start' => ['nullable', 'date'],
            'planned_end' => ['nullable', 'date', 'after_or_equal:planned_start'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
